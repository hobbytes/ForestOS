<?
//Инициализируем переменные
$appid=$_GET['appid'];
$wall=$_GET['wall'];
$theme=$_GET['theme'];
$appname=$_GET['appname'];
$folder=$_GET['destination'];
session_start();
$language_screen  = parse_ini_file('lang/screen.lang');
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px;  padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language_screen[$_SESSION['locale'].'_settings_screen']?></div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/gui.php';
include '../../core/library/etc/security.php';
$security	=	new security;
$fileaction = new fileaction;
$object = new gui;
$security->appprepare();
if($wall!=''){
  $wall_link = '../../../system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg';
  if($wall=='none'){
    if(unlink($wall_link)){
      $object->newnotification($appname,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notwalldelete']);
    }
  }else{
    if(copy('../../../system/core/design/walls/'.$wall, $wall_link)){
      $wall = $fileaction->filehash($wall_link);
      ?>
      <script>
    function wallchange(){
        $("#background-wall").attr("src", "<?echo $wall?>");
    };
    wallchange();
      </script>
    <?
    $object->newnotification($appname,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notwallchange']);
  }
}
}
if ($theme!=''){
  session_start();
  if(copy('../../../system/core/design/themes/'.$theme.'','../../../system/users/'.$_SESSION["loginuser"].'/settings/etc/theme.fth'))  {
  $object->newnotification($appname,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notthemechange_1']."<b>".$theme."</b>. ".$language_screen[$_SESSION['locale'].'_notthemechange_2']."<br><span id='restart' style='margin-left: 25%;' class='ui-button ui-widget ui-corner-all'>".$language_screen[$_SESSION['locale'].'_restart']."</span>");
}else{$object->newnotification($appname,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notthemeerror']);}
}

$displayLink = '../../users/'.$_SESSION["loginuser"].'/settings/display.foc';

if(isset($_GET['scale'])){
  $saveSettings = "[DisplaySetting]\nscale='".$_GET['scale']."'\nrotate='".$_GET['rotate']."'\nsmooth='".$_GET['smooth']."'\ncontrast='".$_GET['contrast']."'\nbrightness='".$_GET['brightness']."'\nsaturate='".$_GET['saturate']."'";
  file_put_contents($displayLink,$saveSettings);
}

if(is_file($displayLink)){
  $getObject = parse_ini_file($displayLink);
}
    ?>

<div id="tabssettings<?echo $appid?>" style="display: inline-block; width: 100%;">
  <ul>
    <li><a href="#mainsettingtab<?echo $appid?>"><?echo $language_screen[$_SESSION['locale'].'_displaytab']?></a></li>
    <li><a href="#themesettingtab<?echo $appid?>"><?echo $language_screen[$_SESSION['locale'].'_themetab']?></a></li>
    <li><a href="#wallsettingtab<?echo $appid?>"><?echo $language_screen[$_SESSION['locale'].'_walltab']?></a></li>
  </ul>

  <div id="mainsettingtab<?echo $appid?>">
    <div style="padding:20px; border: 1px solid #a29d9d; float: left; margin: 10px;">
      <div style="font-size: 20px; font-weight: 900; text-transform: uppercase; padding: 10px 0;"><?echo $language_screen[$_SESSION['locale'].'_correct_label']?></div>
      <div style="margin: 20px auto;">
        <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_contrast_label']?>:</div>
        <div style="width:200px;" id="contrast-filter<?echo $appid?>">
          <div id="contrast-handle<?echo $appid?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
        </div>
      </div>

      <div style="margin: 20px auto;">
        <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_brightness_label']?>:</div>
        <div style="width:200px;" id="brightness-filter<?echo $appid?>">
          <div id="brightness-handle<?echo $appid?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
        </div>
      </div>

      <div style="margin: 20px auto;">
        <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_saturate_label']?>:</div>
        <div style="width:200px;" id="saturate-filter<?echo $appid?>">
          <div id="saturate-handle<?echo $appid?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
        </div>
      </div>

    </div>

    <div style="padding:20px; border: 1px solid #a29d9d; float: left; margin: 10px;">
      <div style="font-size: 20px; font-weight: 900; text-transform: uppercase; padding: 10px 0;"><?echo $language_screen[$_SESSION['locale'].'_display_label']?></div>

      <div style="margin: 20px auto;">
        <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_scale_label']?>:</div>
        <div style="width:200px;" id="scale-display<?echo $appid?>">
          <div id="scale-handle<?echo $appid?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
        </div>
      </div>

      <div style="margin: 20px auto;">
        <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_rotate_label']?>:</div>
        <div style="width:200px;" id="rotate-display<?echo $appid?>">
          <div id="rotate-handle<?echo $appid?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
        </div>
      </div>

      <div style="margin: 20px auto;">
        <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_smooth_label']?>:</div>
        <div style="width:200px;" id="smooth-display<?echo $appid?>">
          <div id="smooth-handle<?echo $appid?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
        </div>
      </div>

    </div>

    <div style="width: 100%; float: left;">
    <div class="ui-forest-button ui-forest-accept ui-forest-center" onClick="savedisplay<?echo $appid?>()">
      <?echo $language_screen[$_SESSION['locale'].'_save_button']?>
    </div>

    <div class="ui-forest-button ui-forest-cancel ui-forest-center" onClick="resetdisplay<?echo $appid?>()">
      <?echo $language_screen[$_SESSION['locale'].'_reset_button']?>
    </div>
  </div>

  </div>

  <div id="themesettingtab<?echo $appid?>">
<?
    $current_theme  = parse_ini_file('../../users/'.$_SESSION["loginuser"].'/settings/etc/theme.fth');
    $current_theme  = $current_theme['Name'];
    $dir2='../../core/design/themes/';
    $d2=dir($dir2);
    chdir($d2->path2);

    while (false !== ($entry2=$d2->read())) {
      $path2=$d2->path2;
      $name2=$entry2;
      if ($entry2!='.' && $entry2!='..'){
        $themeloadset=parse_ini_file('../../core/design/themes/'.$name2);
        if($current_theme == $themeloadset['Name']){
          $select_style  = 'border: 2px solid '.$themeloadset['draggablebackcolor'].';  box-shadow:0 0 5px '.$themeloadset['topbarbackcolor'].';';
          $select_text  = '<span style="font-size:10px;">('.$language_screen[$_SESSION['locale'].'_current_label'].')</span>';
        }
        echo('<div id="'.$name2.'" class="ui-button ui-widget ui-corner-all" onClick="loadtheme'.$appid.'(this);" style="-webkit-user-select:none; cursor:pointer; '.$select_style.' user-select:none; padding:5px; background-color:'.$themeloadset['backgroundcolor'].'; margin:5px; color:'.$themeloadset['backgroundfontcolor'].'; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; text-transform:uppercase; "><div style="height:15%; background-color:'.$themeloadset['topbarbackcolor'].';"></div><div style="height:15%; background-color:'.$themeloadset['draggablebackcolor'].'; margin-bottom: 5px;"></div>'.$themeloadset['Name'].'<br>'.$select_text.'</div>');
        $select_style  = '';
        $select_text  = '';
    }}
    $dir2->close;
?>
</div>

<div id="wallsettingtab<?echo $appid?>">
  <?
  $dir='../../core/design/walls/';
  $d=dir($dir);
  chdir($d->path);
  echo('<div id="clearwall" class="ui-button ui-widget ui-corner-all" onClick="clearwall'.$appid.'();" style="-webkit-user-select:none; border:2px dashed #4a4a4a; background-color:#e4e4e4; cursor:pointer; user-select:none; padding:5px; margin:5px; color:#000; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; ">'.$language_screen[$_SESSION['locale'].'_clear_label'].'</div>');
  while (false !== ($entry=$d->read())) {
  	$path=$d->path;
  	$name=$entry;
    $color='#80abc6';
  	if ($entry!='.' && $entry!='..'){
  	echo('<div id="'.$name.'" class="ui-button ui-widget ui-corner-all" onClick="loadwall'.$appid.'(this);" style="-webkit-user-select:none; cursor:pointer; user-select:none; padding:5px; background-color:'.$color.'; margin:5px; color:#000; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; background-color:transparent;  background-image: url(../../system/core/design/walls/'.$name.'); background-size:cover;"></div>');
  }}
  $dir->close;
  ?>
</div>
</div>
</div>
<script>
  cHandler = $("#contrast-handle<?echo $appid?>");
  bHandler = $("#brightness-handle<?echo $appid?>");
  sHandler = $("#saturate-handle<?echo $appid?>");
  scaleHandler = $("#scale-handle<?echo $appid?>");
  rotateHandler = $("#rotate-handle<?echo $appid?>");
  smoothHandler = $("#smooth-handle<?echo $appid?>");
  var state = '<?echo $getObject['scale'];?>';
  if( state ){
    scaleValue = '<?echo $getObject['scale'];?>';
    rotateValue = '<?echo $getObject['rotate'];?>';
    smoothValue = '<?echo $getObject['smooth'];?>';
    contrastValue = '<?echo $getObject['contrast'];?>';
    brightnessValue = '<?echo $getObject['brightness'];?>';
    saturateValue = '<?echo $getObject['saturate'];?>';
  }else{
    scaleValue = '1';
    rotateValue = '0';
    smoothValue = '0.5';
    contrastValue = '1';
    brightnessValue = '1';
    saturateValue = '1';
  }

  $("#tabssettings<?echo $appid?>").tabs();

  $("#contrast-filter<?echo $appid?>").slider({
    value: contrastValue,
    min: 0.7,
    max: 1.4,
    step:0.1,
    create: function(){
      cHandler.text(contrastValue);
    },
    slide: function(event, ui){
      cHandler.text(ui.value);
      contrastValue = ui.value;
      getFilter();
    }
  });

  $("#brightness-filter<?echo $appid?>").slider({
    value: brightnessValue,
    min: 0.3,
    max: 1.11,
    step:0.1,
    create: function(){
      bHandler.text(brightnessValue);
    },
    slide: function(event, ui){
      bHandler.text(ui.value);
      brightnessValue = ui.value;
      getFilter();
    }
  });

  $("#saturate-filter<?echo $appid?>").slider({
    value: saturateValue,
    min: 0,
    max: 2,
    step:0.1,
    create: function(){
      sHandler.text(saturateValue);
    },
    slide: function(event, ui){
      saturateValue = ui.value;
      sHandler.text(ui.value);
      getFilter();
    }
  });

  $("#scale-display<?echo $appid?>").slider({
    value: scaleValue,
    min: 0.7,
    max: 1.4,
    step:0.01,
    create: function(){
      scaleHandler.text(scaleValue);
    },
    slide: function(event, ui){
      scaleHandler.text(ui.value);
      scaleValue = ui.value;
      getDisplay();
    }
  });

  $("#rotate-display<?echo $appid?>").slider({
    value: rotateValue,
    min: -190,
    max: 190,
    step:10,
    create: function(){
      rotateHandler.text(rotateValue);
    },
    slide: function(event, ui){
      rotateValue = ui.value;
      getDisplay();
      rotateHandler.text(ui.value);
      if(rotateHandler.text >= 170 || ui.value >= 170){
        $("#rotate-display<?echo $appid?>").slider('value',180);
        rotateHandler.text(ui.value);
        getDisplay();
      }

      if(rotateHandler.text <= -170 || ui.value <= -170){
        $("#rotate-display<?echo $appid?>").slider('value',-180);
        rotateHandler.text(ui.value);
        getDisplay();
      }
    }
  });

  $("#smooth-display<?echo $appid?>").slider({
    value: smoothValue,
    min: 0,
    max: 1.5,
    step:0.1,
    create: function(){
      smoothHandler.text(smoothValue);
    },
    slide: function(event, ui){
      smoothValue = ui.value;
      smoothHandler.text(ui.value);
      $('.backgroundtheme').css('transition','all '+ui.value+'s ease');
    }
  });

  function getFilter(){
    var valueFilter = 'contrast('+contrastValue+') brightness('+brightnessValue+') saturate('+saturateValue+')';
    $('.backgroundtheme').css('filter',valueFilter);
  }

  function getDisplay(){
    var valueDisplay = 'rotate('+rotateValue+'deg) scale('+scaleValue+','+scaleValue+')';
    $('.backgroundtheme').css('transform',valueDisplay);
  }


$( "#restart" ).on( "click", function() {return location.href = 'os.php';});
function loadwall<?echo $appid?>(el){
  if(el!='none'){
    el=el.id;
  }
  $("#<?echo $appid?>").load("<?echo $folder?>screen.php?wall="+el+"&id=<?echo rand(0,10000).'&appname='.$appname.'&destination='.$folder.'&appid='.$appid;?>")};
function loadtheme<?echo $appid?>(el2){$("#<?echo $appid?>").load("<?echo $folder?>screen.php?theme="+el2.id+"&id=<?echo rand(0,10000).'&appname='.$appname.'&destination='.$folder.'&appid='.$appid;?>")};
function back<?echo $appid?>(el){$("#<?echo $appid?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function clearwall<?echo $appid?>(){
  $('#background-wall').attr('src','data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=');
  loadwall<?echo $appid?>('none');
}

function savedisplay<?echo $appid?>(){
  $("#<?echo $appid?>").load("<?echo $folder?>screen.php?scale="+scaleValue+"&rotate="+rotateValue+"&smooth="+smoothValue+"&contrast="+contrastValue+"&brightness="+brightnessValue+"&saturate="+saturateValue+"&id=<?echo rand(0,10000).'&appname='.$appname.'&destination='.$folder.'&appid='.$appid;?>")
}

function resetdisplay<?echo $appid?>(){
    scaleValue = '1';
    rotateValue = '0';
    smoothValue = '0.5';
    contrastValue = '1';
    brightnessValue = '1';
    saturateValue = '1';
    getFilter();
    getDisplay();
    $('.backgroundtheme').css('transition','all '+smoothValue+'s ease');
    savedisplay<?echo $appid?>();
}
UpdateWindow("<?echo $appid?>","<?echo $appname?>");
</script>
