<?php

class Telegram {
    public static function check_files_availability($array, $path): array {
        $response = [
            'checked' => true
        ];
        $files = [];
        foreach ($array['name'] as $index => $name) {
            $files[$index] = [];
            $files[$index]['path'] = $path . self::get_current_filename($name);
            $files[$index]['available'] = file_exists($files[$index]['path']);
        }

        foreach ($files as $file) {
            if (!$file['available']) {
                $response['checked'] = false;
            }
        }

        $response['files'] = $files;

        return $response;
    }

    public static function delete_files($path) {
        if (file_exists($path)) {
            foreach (glob($path . '*') as $file) {
                unlink($file);
            }
        }
    }

    public static function get_current_filename($name) {
        return preg_replace('/\s+/', '_', sanitize_text_field(basename($name)));
    }

    public static function get_media_for_send($files, $url_to_photo, $message = '') {
        if (isset($files)) {
            $media = [];
            foreach ($files['tmp_name'] as $key => $file) {
                $media[] = ['type' => 'photo', 'media' => $url_to_photo . Telegram::get_current_filename($files['name'][$key])];
                if ($key === 0) {
                    $media[$key]['caption'] = $message;
                }
            }
            return $media;
        }
        return false;
    }

    public static function move_files_to_folder($files, $path) {
        if (isset($files)) {
            foreach ($files['tmp_name'] as $key => $file) {
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }
                $correct_file_name = Telegram::get_current_filename($files['name'][$key]);

                move_uploaded_file($files['tmp_name'][$key], $path . $correct_file_name);
            }
        }
    }

    public static function get_message($data) {
        $msg = '';
        foreach ($data as $name => $param) {
            if ($name != 'chspel' && $name != 'tg_chats' && $name != 'photos') {
                if (!empty($param)) {
                    if ($name === 'phone' || $name === 'телефон') {
                        $msg .= "$name:%20%20" . preg_replace('/\s/', '%20', $param) . "%0A";
                    } else if ($name === 'sum' || $name === 'сумма') {
                        $msg .= "$name:%20%20" . $param . "₽" . "%0A";
                    } else {
                        $msg .= "$name:%20%20" . $param . "%0A";
                    }
                }
            }
        }
        return $msg;
    }

    public static function get_url($bot_token, $method, $chat_id, $data) {
        return 'https://api.telegram.org/bot' . $bot_token . '/' . $method . '?chat_id=' . trim($chat_id) . $data;
    }
}