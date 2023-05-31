<?php
require_once THEME_PATH . '/telegram/classes/Telegram.php';

add_action('rest_api_init', function () {

    $namespace = 'tg-api/v3';

    $rout = 'send';

    $rout_params = [
        'methods'             => 'POST',
        'callback'            => 'tg_api_send_v3',
        'permission_callback' => '__return_true',
    ];

    register_rest_route($namespace, $rout, $rout_params);

});
function tg_api_send_v3(WP_REST_Request $request) {

    $bots = get_field('tg_bots', 'telegram') ?? null;
    $tg_chats = null;
    $spam = $request->get_params()['chspel'] ?? null;
    $path_to_photos = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/girl-jobs/telegram/photos/';
    $url_to_photo = get_template_directory_uri() . '/telegram/photos/';
    $send_data = [];


    if (isset($spam) && $spam === "") {
        $response = new WP_HTTP_Response();
        $error = new WP_Error();
        $parameters = $request->get_params();
        $data = [];

        if ($parameters) {
            foreach ($parameters as $name => $value) {
                $data[$name] = sanitize_text_field($value);
            }
        }

        if (empty($data['tg_chats'])) {
            $error->add(400, __("Не указан чат(ы) для отправки", 'error-no-chat'), ['status' => 400]);
        } else {
            $tg_chats = explode(",", $data['tg_chats']);
        }

        $isMedia = !empty($_FILES['files']);

        Telegram::move_files_to_folder($_FILES['files'], $path_to_photos);

        $message = Telegram::get_message($data);
        $media = Telegram::get_media_for_send($_FILES['files'], $url_to_photo, $message);
        $media_files = '&media=' . json_encode($media);
        $media_text = '&parse_mode=html&text=' . $message;
        $method = $isMedia ? 'sendMediaGroup' : 'sendMessage';
        $content = $isMedia ? $media_files : $media_text;

        $post = function ($link) use ($error, $path_to_photos, $response, &$send_data) {
            $check_files = Telegram::check_files_availability($_FILES['files'], $path_to_photos);
            $res_data = null;

            $curl_options = array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_VERBOSE        => TRUE,
//                CURLOPT_STDERR         => $verbose = fopen('php://temp', 'rw+'),
            );

            if ($check_files['checked']) {
                try {
                    $ch = curl_init($link);
                    curl_setopt_array($ch, $curl_options);
                    $res_data = curl_exec($ch);
                    //                    $send_data['curl_info'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                    //                    $send_data['curl_verbose'] = "Verbose information:\n<pre>" . !rewind($verbose) . htmlspecialchars(stream_get_contents($verbose)) . "</pre>\n";
                    curl_close($ch);

                } catch (Exception $ex) {
                    return $ex->getMessage();
                }

                return json_decode($res_data);
            } else {
                $error->add(400, __("Не удалось найти файл для отправки", 'error-photos'), ['status' => 400]);
            }
        };

        if (!empty($bots)) {
            if (!isset($send_data['chats'])) {
                $send_data['chats'] = [];
            }

            foreach ($bots as $bot) {
                if (count($tg_chats) > 1) {
                    foreach ($tg_chats as $chat) {
                        $send_data['chats'][$chat] = $post(Telegram::get_url($bot['token'], $method, $chat, $content));
                    }
                } else {
                    $send_data['chats'][$tg_chats[0]] = $post(Telegram::get_url($bot['token'], $method, $tg_chats[0], $content));
                }
            }

            Telegram::delete_files($path_to_photos);

            $response->set_data($send_data);

        } else {
            $error->add(400, __("Отсутствует бот токен", 'error-no-bots'), ['status' => 400]);
        }

        return $error->has_errors() ? $error : $response;
    } else {
        return ['spam'];
    }
}
