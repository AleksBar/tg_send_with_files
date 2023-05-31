Собираем FormData из формы и отправляем на нужный url

Файлы собираем так:
(Ванильный JS)
let formData = new FormData(form)

const files = form.querySelector('input[type="file"]').files
if(files){
      for(let i = 0; i < files.length; i++){
         formData.append('files[]', files[i]);
      }
}

Так же добавляем id телеграм чата:
formData.append('tg_chats', chat)

Ещё в форме должен присутствовать антиспам поле:
<input type="hidden" name="chspel" value="">