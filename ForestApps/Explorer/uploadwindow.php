<?
$where=$_GET['where'];
$appname=$_GET['appname'];
$data = array();

if( isset( $_GET['uploadfiles'] ) ){
    $error = false;
    $files = array();

    $uploaddir = $where; // . - текущая папка где находится submit.php

    // Создадим папку если её нет

    if( ! is_dir( $uploaddir ) ) mkdir( $uploaddir, 0777 );

    // переместим файлы из временной директории в указанную
    foreach( $_FILES as $file ){
        if( move_uploaded_file( $file['tmp_name'], $uploaddir . basename($file['name']) ) ){
            $files[] = realpath( $uploaddir . $file['name'] );
        }
        else{
            $error = true;
        }
    }

    $data = $error ? array('error' => 'Ошибка загрузки файлов.') : array('files' => $files );

    echo json_encode( $data );
}
?>
<div style="margin-top:120px; text-align:center; width:100%;">
  Загрузка файла<br>
<input type="file" multiple="multiple" accept="*">
<p style="background-color:#dc3333; color:#fff; width:40%; padding:10px; border-radius:5px; cursor:pointer; margin:auto; margin-top:10px;" class="submit button">Загрузить файлы</p>
<div class="ajax-respond"></div></div>
<script>
// Переменная куда будут располагаться данные файлов

var files;

// Вешаем функцию на событие
// Получим данные файлов и добавим их в переменную

$('input[type=file]').change(function(){
    files = this.files;
});

// Вешаем функцию ан событие click и отправляем AJAX запрос с данными файлов

$('.submit.button').click(function( event ){
    event.stopPropagation(); // Остановка происходящего
    event.preventDefault();  // Полная остановка происходящего

    // Создадим данные формы и добавим в них данные файлов из files

    var data = new FormData();
    $.each( files, function( key, value ){
        data.append( key, value );
    });

    // Отправляем запрос

    $.ajax({
        url: '/system/apps/<?echo $appname;?>/uploadwindow.php?where=<?echo $where;?>&uploadfiles',
        type: 'POST',
        data: data,
        cache: false,
        dataType: 'json',
        processData: false, // Не обрабатываем файлы (Don't process the files)
        contentType: false, // Так jQuery скажет серверу что это строковой запрос
        success: function( respond, textStatus, jqXHR ){

            // Если все ОК

            if( typeof respond.error === 'undefined' ){
                // Файлы успешно загружены, делаем что нибудь здесь

                // выведем пути к загруженным файлам в блок '.ajax-respond'

                var files_path = respond.files;
                var html = '';
                $.each( files_path, function( key, val ){ html += val +'<br>'; } )
                $('.ajax-respond').html( html );
            }
            else{
                console.log('ОШИБКИ ОТВЕТА сервера: ' + respond.error );
            }
        },
        error: function( jqXHR, textStatus, errorThrown ){
            console.log('ОШИБКИ AJAX запроса: ' + textStatus );
        }
    });

});
</script>
