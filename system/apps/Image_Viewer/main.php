<?
/*Image Viewer*/

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;

/* App Info */
$AppContainer->AppNameInfo = 'Image Viewer';
$AppContainer->SecondNameInfo = 'Просмотр изображений';
$AppContainer->VersionInfo = '1.2.1';
$AppContainer->AuthorInfo = 'Forest Media';

/* Container Info */
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->LibraryArray = array('filesystem', 'gui', 'permissions');
$AppContainer->backgroundColor = 'var(--fo-win-body, #fbfbfd)';
$AppContainer->fontColor = 'var(--fo-text, #151a17)';
$AppContainer->height = '100%';
$AppContainer->width = '100%';
$AppContainer->customStyle = 'padding-top:0px; overflow:auto; min-width:300px; min-height:200px; ';
$AppContainer->StartContainer();


$hash = new fileaction;
$object = new gui;
$newpermission = new PermissionRequest;
$security	=	new security;
$click = $_GET['mobile'];
$Folder = $_GET['destination'];

$_dest = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_GET['defaultloader']);
if(empty($_dest)){
  $_dest = $_GET['photoviewload'];
}

$result = explode('?', $_dest);
$_dest = $result[0];

$dest = $hash->filehash('../../../'.$_dest, 'false');

//Ассоциируем файлы
$newpermission->fileassociate(array('png','jpg','jpeg','bmp','gif'), $Folder.'main.php', 'photoviewload', $AppName);

if($dest  ==  ''){
  $dest = $_dest;
}

$photo = $dest;

/*local file?*/
$isLocal = realpath((dirname($dest)));

if(!empty($isLocal)){
  $download = 'false';
}else{
  $download = 'true';
}

function setwall($photo, $downloadDir, $dest, $hash){
  if(is_file($photo)){
    $_photo = $photo;
  }else{
    $_photo = $downloadDir.'/'.basename($dest);
  }
  $wall_link = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg';

  if(copy($_photo, $wall_link)){
    $wall = $hash->filehash($wall_link);
    $wall = str_replace($_SERVER['DOCUMENT_ROOT'], '', $wall);
    ?>
    <script>
    $("#background-wall").attr("src", "<?echo $wall?>");
    </script>
  <?
}
}

/*download image*/
if($_GET['download'] == 'true'){
  $downloadDir = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/documents/images';
  if(!is_dir($downloadDir)){
    mkdir($downloadDir);
  }
  $ch = curl_init($dest);
  $fp = fopen($downloadDir.'/'.basename($dest),'wb');
  curl_setopt($ch, CURLOPT_FILE,$fp);
  curl_setopt($ch, CURLOPT_HEADER,0);
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);
  if(is_file($downloadDir.'/'.basename($dest))){
    if(!isset($_GET['setwall'])){

    ?>
    <script>
      makeprocess("system/apps/Explorer/main.php" , "<?echo $downloadDir?>", "dir", "Explorer");
    </script>
    <?
  }else{
    setwall($photo, $downloadDir, $dest, $hash);
  }
}
}else{
  if(isset($_GET['setwall'])){
    setwall($photo, $downloadDir, $dest, $hash);
  }
}

?>

<style>
<?echo '#'.$AppName.$AppID;?> {
  background-color: #3e3d40;
  transition: background-color 0.3s ease-out;
  overflow-y: hidden;
}
<?echo '#'.$AppName.$AppID;?>.zoom {
  background-color: #262626;
}

<?echo ".photo".$AppID;?> {
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  margin: auto;
  transition: all 0.2s ease-out;
  transform: scale(1);
  width:100%;
}
<?echo ".photo".$AppID;?>:hover {
  box-shadow:rgba(16, 16, 22, 0.2) 0px 0px 1px 1px;
}

<?echo ".button".$AppID;?> {
  width: 30px;
  height: 30px;
  background-color: #000000;
  border-radius: 2px;
  position: absolute;
  right: 5%;
  top: 50%;
  transition: all 0.2s ease-in-out;
  box-shadow: 0px 0px 2px 2px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  opacity: 0.5;
}
<?echo ".button".$AppID;?>:hover {
  opacity: 1;
}
<?echo ".button".$AppID;?> i.material-icons {
  color: #fff;
  padding: 3px 3px;
  user-select: none;
}

<?echo ".zoom".$AppID;?><?echo " .button".$AppID;?>  {
  right: 20px;
}

<?echo ".zoom-in".$AppID;?> {
  margin-top: 15px;
}

<?echo ".zoom-out".$AppID;?> {
  margin-top: -20px;
}

<?echo ".setwall".$AppID;?> {
  margin-top: 50px;
}

</style>
<div style="width:100%; height:100%;">
	<img src="<?echo $photo;?>" id="photo<?echo $AppName.$AppID?>" class="photo<?echo $AppID?>">
</div>
<div class="button<?echo $AppID?> zoom-in<?echo $AppID?>" data-fo-action="click" data-fo-event="click" title="Уменьшить"><i class="material-icons">-</i></div>
<div class="button<?echo $AppID?> zoom-out<?echo $AppID?>" data-fo-action="click" data-fo-event="click" title="Увеличить"><i class="material-icons">+</i></div>
<div class="button<?echo $AppID?> setwall<?echo $AppID?>" messageTitle="Set the image?" messageBody="Make a desktop background image?" okButton="Set wall" cancelButton="Cancel" onclick="ExecuteFunctionRequest<?echo $AppID?>(this, 'setwall<?echo $AppID?>')"><span class="material-icons">wall</span></div>
<?
if(empty($isLocal)){
  ?>
  <div class="ui-forest-blink" id="downloadImage<?echo $AppID?>" style="background:rgba(243,243,243,0.9); text-align:center; position:absolute; top:88%; left:46%; padding:0 20px; color:#8BC34A; font-size:30px; font-weight:900; border-radius:7px; border:2px solid #afafaf;">&#11015;</div>
  <?
}
$AppContainer->EndContainer();
?>
<script>

<?

// prepare request
$AppContainer->ExecuteFunctionRequest();

?>

var zoom = 1;
$(document).ready(function(){

  $('.zoom-in<?echo $AppID?>').click(function(){
    zoom-=1;
    var k = parseFloat(1+zoom/7);
    $('.photo<?echo $AppID?>').css('transform','scale('+k+')');
  });
  $('.zoom-out<?echo $AppID?>').click(function(){
    zoom+=1;
    var k = parseFloat(1+zoom/7);
    $('.photo<?echo $AppID?>').css('transform','scale('+k+')');
  });
});

/*download image*/
$('#downloadImage<?echo $AppID?>').click(function(){
  $("#<?echo $AppID?>").load("<?echo $Folder;?>main.php?photoviewload=<?echo $dest?>&download=true&id=<?echo rand(0,10000).'&appid='.$AppID.'&mobile='.$click.'&appname='.$AppName.'&destination='.$Folder;?>");
});

/*set wall*/
function setwall<?echo $AppID?>(){
  $("#<?echo $AppID?>").load("<?echo $Folder;?>main.php?photoviewload=<?echo $dest?>&setwall=true&download=<?echo $download?>&id=<?echo rand(0,10000).'&appid='.$AppID.'&mobile='.$click.'&appname='.$AppName.'&destination='.$Folder;?>");
};

$( function() {
  $( "#photo<?echo $AppName.$AppID;?>" ).draggable();
});
</script>
<?
unset($AppID);
?>
