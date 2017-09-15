<?
/*--------Получаем App Name и App ID--------*/
$appname  = $_GET['appname'];
$appid  = $_GET['appid'];
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:100%; width:100%; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
include '../../core/library/filesystem.php';
include '../../core/library/etc/security.php';
$fo = new filecalc;
$security	=	new security;
$click=$_GET['mobile'];
$folder=$_GET['destination'];
/*--------Запускаем сессию--------*/
session_start();
$security->appprepare();
/*--------Логика--------*/
$get_object = $_GET['object'];
if(is_file($get_object)){
  $extension	=	stristr($get_object, '.');
  $extension	=	str_replace('.','',$extension);
  $type = '*.'.$extension;
  $fo->format(filesize(realpath($get_object)));
}else{
  $type = 'Папка с файлами';
  try {
    $fo->size_check(realpath(realpath($get_object)));
    $fo->format($size);
    if (empty($size)){
      $format	= '0 Bytes';
    }
    $format = $format;
  } catch (Exception $e) {
    echo $e->getMessage($e);
  }
}
$name_parts = pathinfo($get_object);
$name = str_replace('.'.$name_parts['extension'],'',$name_parts['basename']);
$pathmain = str_replace($_SERVER['DOCUMENT_ROOT'],'',$get_object);
?>
<div style="padding:10px; max-width:400px; word-break:break-word;">
  <div style="margin:5px auto; text-transform:uppercase; color:#295eb1; font-size:24px; font-weight:900;">
    <?
    echo $name;
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo '<b>Тип:</b> '.$type;
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo '<b>Расположение:</b> os'.$pathmain;
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo '<b>Размер:</b> '.$format;
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo '<b>Дата последнего изменения:</b> '.date('d.m.y, H:i:s', filectime(realpath($get_object)));
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo "<b>Атрибуты:</b><br>".(is_readable($get_object) ? "" : "не")."доступен для чтения<br>".(is_writable($get_object) ? "" : "не")."доступен для записи";
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo '<b>Прямая ссылка:</b> <a class="ui-forest" style="background-color:#de4949; color:#fff; padding:3px; border:1px solid #5d1515; border-radius:5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" href="'.$pathmain.'" target="_blank">'.$name.' &#x1f517;</a>';
    ?>
  </div>
</div>
</div>
<?
/*--------Очищаем переменную $appid--------*/
?>
