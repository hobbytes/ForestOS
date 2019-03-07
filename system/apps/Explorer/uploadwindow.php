<?

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
$security	=	new security;

$security->appprepare();

//Загружаем файл локализации
$upload_lang  = parse_ini_file('assets/lang/etc.lang');
$cl = $_SESSION['locale'];
if(isset($_GET['where'])){
  $where = str_replace(' ', '_', strip_tags($_GET['where']));
}else{
  $where = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/documents/downloads/';
  if(!is_dir($where)){
    mkdir($where);
  }
}


$appname = $_GET['appname'];
$AppID = $_GET['appid'];
$folder = $_GET['destination'];

$data = array();
if(isset($_GET['uploadfiles'])){
  if($_SESSION['superuser'] == $_SESSION['loginuser']){
    $error = false;
    $files = array();
    $uploaddir = $where;
    if( ! is_dir( $uploaddir ) ) mkdir( $uploaddir, 0777 );
    foreach( $_FILES as $file ){
        if( move_uploaded_file( $file['tmp_name'], $uploaddir . str_replace( " ", "_", iconv( "UTF8", "UTF8//TRANSLIT", $file['name'] ) ) ) ){
            $files[] = realpath( $uploaddir . str_replace(" ", "_", $file['name']) );
        }
        else{
            $error = true;
        }
    }

    $data = $error ? array('error' => 'Error') : array('files' => $files );

    $response = json_encode( $data );
    header('Content-Type: application/json');
    header('Accept-Ranges: bytes');
    header('Content-Length: '. mb_strlen($response, 'UTF-8'));
    echo $response;
  }else{
    echo 'private error!';
    exit;
  }
}
?>
<div class="upload-container<?echo $AppID?>" style="text-align:center; background:#ededed; min-width:400px; height: 100%;">
  <?echo $upload_lang[$cl.'_upload']?><br><br>
<input type="file" multiple="multiple" accept="*">
<div class="file-list<?echo $AppID?>" style="padding: 10px 0 0 0;">

</div>
<div id="progressbar<?echo $AppID?>" style="display: none; width: 80%; margin: 10px auto; border: none; height: 10px; transition: all 0.2s ease"></div>
<div class="submit button ui-forest-button ui-forest-accept ui-forest-center"><?echo $upload_lang[$cl.'_btn_load']?></div>
<div onClick="hideload<?echo $AppID?>();" class="ui-forest-button ui-forest-cancel ui-forest-center"><?echo $upload_lang[$cl.'_btn_cancel']?></div>
</div>
<script>

var files = new Array;

$('input[type=file]').change(function(){
    files.push(this.files);
    $('.file-list<?echo $AppID?>').append(this.files[0].name + "<br>");
});

$("#progressbar<?echo $AppID?>").progressbar({
  value: 0
});

$("#progressbar<?echo $AppID?>").find('.ui-progressbar-value').css('background', '#8BC34A');

function hideload<?echo $AppID?>(){
  event.stopPropagation();
  event.preventDefault();
  if(typeof reload<?echo $AppID?> !== 'undefined' && $.isFunction(reload<?echo $AppID?>)){
    reload<?echo $AppID?>();
  }else{
    $('#process<?echo $AppID?>').remove();
  }
}

$(document).ready(function() {
  $('.upload-container<?echo $AppID?>').bind('dragover drop', function(event) {
    event.stopPropagation();
    event.preventDefault();
    if (event.type == 'drop') {
      files_ = event.target.files || event.originalEvent.dataTransfer.files;
      $('.file-list<?echo $AppID?>').append(files_[0].name + "<br>");
      files.push(files_);
      console.log(files);

    }
  });
});

$('.submit.button').click(function( event ){
  $("#progressbar<?echo $AppID?>").css('display', 'block');
    event.stopPropagation();
    event.preventDefault();

    var data = new FormData();
    for (var i = 0, f; f = files[i]; i++) {
      $.each( f, function( key, value ){
          data.append( i, value );
      });
    }


    $.ajax({
        xhr: function(){

          var xhr = new window.XMLHttpRequest();

          xhr.upload.addEventListener("progress", function(evt){
            if(evt.lengthComputable){
              var percentComplete = (evt.loaded / evt.total * 100);
              $("#progressbar<?echo $AppID?>").progressbar("value", percentComplete);
              if(percentComplete == 100){
                hideload<?echo $AppID?>();
              }
            }
          }, false);

          return xhr;
        },
        url: '<?echo $folder?>/uploadwindow.php?where=<?echo $where?>&uploadfiles',
        type: 'POST',
        data: data,
        cache: false,
        dataType: 'json',
        processData: false,
        contentType: false,
        success: function( response ){
              //hideload<?echo $AppID?>();
              //console.log('ОШИБКИ ОТВЕТА сервера: ' + respond.error );
            }
    });

});
</script>
