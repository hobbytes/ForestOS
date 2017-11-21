<?
include '../../core/library/etc/security.php';
$security	=	new security;
session_start();
$security->appprepare();

//Загружаем файл локализации
$upload_lang  = parse_ini_file('assets/lang/etc.lang');
$cl = $_SESSION['locale'];

$where=$_GET['where'];
$appname=$_GET['appname'];
$appid=$_GET['appid'];
$folder=$_GET['destination'];
$data = array();
if(isset($_GET['uploadfiles'])){
  if($_SESSION['superuser'] == $_SESSION['loginuser']){
    $error = false;
    $files = array();
    $uploaddir = $where;
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

    $data = $error ? array('error' => 'Error') : array('files' => $files );

    echo json_encode( $data );
  }else{
    echo 'private error!';
    exit;
  }
}
?>
<div style="text-align:center;">
  <?echo $upload_lang[$cl.'_upload']?><br><br>
<input type="file" multiple="multiple" accept="*">
<div class="submit button ui-forest-button ui-forest-accept ui-forest-center"><?echo $upload_lang[$cl.'_btn_load']?></div>
<div onClick="hideload<?echo $appid?>();" class="ui-forest-button ui-forest-cancel ui-forest-center"><?echo $upload_lang[$cl.'_btn_cancel']?></div>
<div class="ajax-respond"></div></div>
<script>
// Переменная куда будут располагаться данные файлов

var files;

// Вешаем функцию на событие
// Получим данные файлов и добавим их в переменную

$('input[type=file]').change(function(){
    files = this.files;
});


function hideload<?echo $appid?>(){
  event.stopPropagation(); // Остановка происходящего
  event.preventDefault();  // Полная остановка происходящего
  $('#upload<?echo $appid;?>').html('');
  $("#upload<?echo $appid;?>").css('display', 'none');
}

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
        url: '<?echo $folder?>/uploadwindow.php?where=<?echo $where;?>&uploadfiles',
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
                hideload<?echo $appid?>();
            }
            else{
              hideload<?echo $appid?>();
                //console.log('ОШИБКИ ОТВЕТА сервера: ' + respond.error );
            }
        },
        error: function( jqXHR, textStatus, errorThrown ){
          hideload<?echo $appid?>();
            //console.log('ОШИБКИ AJAX запроса: ' + textStatus );
        }
    });

});
</script>
