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
/*--------Загружаем файл локализации--------*/
$prop_lang  = parse_ini_file('assets/lang/etc.lang');
$cl = $_SESSION['locale'];
/*--------Логика--------*/
$get_object = preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))',$_GET['object']);

/*rename object*/
if(isset($_GET['rename']) && !preg_match('/os.php/',$get_object) && !preg_match('/login.php/',$get_object) && !preg_match('/makeprocess.php/',$get_object)){
  $newname  = preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))',$_GET['rename']);
  if(rename($get_object, dirname($get_object).'/'.$newname)){
    $get_object = dirname($get_object).'/'.$newname;
  }
}

if(is_file($get_object)){
  $extension	=	stristr($get_object, '.');
  $extension	=	str_replace('.','',$extension);
  $type = '*.'.$extension;
  $fo->format(filesize(realpath($get_object)));
}else{
  $type = $prop_lang[$cl.'_prop_type'];
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

/*file or folder?*/
if(is_file($_SERVER['DOCUMENT_ROOT'].$pathmain)){
  $object = $name.'.'.$extension;
}else{
  $object = $name;
}
?>
<div style="padding:10px; max-width:400px; word-break:break-word;">
  <div style="margin:5px auto; text-transform:uppercase; color:#295eb1; font-size:24px; font-weight:900;">
    <?
    echo $name;
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo '<b>'.$prop_lang[$cl.'_prop_type_label'].':</b> '.$type;
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo '<b>'.$prop_lang[$cl.'_prop_destination_label'].':</b> os'.$pathmain;
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo '<b>'.$prop_lang[$cl.'_prop_size_label'].':</b> '.$format;
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo '<b>'.$prop_lang[$cl.'_prop_date_label'].':</b> '.date('d.m.y, H:i:s', filectime(realpath($get_object)));
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
    echo "<b>".$prop_lang[$cl.'_prop_attr_label'].":</b><br>-".(is_readable($get_object) ? "" : $prop_lang[$cl.'_attr_false']).$prop_lang[$cl.'_attr_1']."<br>-".(is_writable($get_object) ? "" : $prop_lang[$cl.'_attr_false']).$prop_lang[$cl.'_attr_2'];
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0; text-align:center;">
    <?
      echo '<input id="renameInput'.$appid.'" style="font-size:15px;" type="text" value="'.$object.'"><span id="renameFile'.$appid.'" class="ui-forest-blink" style="background-color:#4986de; color:#fff; padding:3px; border:1px solid #15545d; margin:0 10; border-radius:5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">'.$prop_lang[$cl.'_prop_rename_label'].'</span>';
    ?>
  </div>
  <div style="margin:10px auto; border-bottom:2px solid #c5bbbb; padding:5px 0;">
    <?
      echo '<b>'.$prop_lang[$cl.'_prop_link_label'].':</b> <a class="ui-forest-blink" style="background-color:#de4949; color:#fff; padding:3px; border:1px solid #5d1515; border-radius:5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" href="'.$pathmain.'" target="_blank">'.$name.' &#x1f517;</a>';
    ?>
  </div>
</div>
</div>
<script>

/*download image*/
$('#renameFile<?echo $appid?>').click(function(){
  $("#<?echo $appid?>").load("<?echo $folder?>property.php?object=<?echo $get_object?>&rename="+escape($("#renameInput<?echo $appid?>").val())+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>");
});
</script>
