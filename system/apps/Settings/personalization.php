<?
/* Personalization */

/* get load data */
$AppID = $_GET['appid'];
$wall = $_GET['wall'];
$theme = $_GET['theme'];
$AppName = $_GET['appname'];
$Folder = $_GET['destination'];

/* get localization file */
$language_screen  = parse_ini_file('lang/personalization.lang');

require '../../core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->LibraryArray = array('filesystem', 'bd', 'gui');
$AppContainer->StartContainer();

?>

<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $AppID?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668; </span><?echo $language_screen[$_SESSION['locale'].'_settings_screen']?></div>

<?php

/* make new object */
$fileaction = new fileaction;
$object = new gui;

/* get data */
$activeTab = 0;
if(isset($_GET['activetab'])){
  $activeTab = $_GET['activetab'];
}


$_folder_wall = $_SERVER['DOCUMENT_ROOT'].'/system/core/design/images/';

if(isset($_GET['wbmode'])){
  $_wbmode = $_GET['wbmode'];
  if($_wbmode == '2'){
    file_put_contents($_folder_wall.'webwallOFF.foc','');
    unlink($_folder_wall.'webwall.jpg');
    $wbmode = '2';
  }else{
    unlink($_folder_wall.'webwallOFF.foc');
    $wbmode = '1';
  }
}else{
  $wbmode = '1';
  if(is_file($_folder_wall.'webwallOFF.foc')){
    $wbmode = '2';
  }
}


if(!empty($wall)){
  $wall_link = '../../../system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg';
  if($wall=='none'){
    if(unlink($wall_link)){
      $object->newnotification($AppName,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notwalldelete']);
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
    $object->newnotification($AppName,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notwallchange']);
  }
}
}
if ($theme!=''){
  if(copy('../../../system/core/design/themes/'.$theme.'','../../../system/users/'.$_SESSION["loginuser"].'/settings/etc/theme.fth'))  {
    $object->newnotification($AppName,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notthemechange_1']."<b>".$theme."</b>. ".$language_screen[$_SESSION['locale'].'_notthemechange_2']."<br><span id='restart' style='margin-left: 25%;' class='ui-button ui-widget ui-corner-all'>".$language_screen[$_SESSION['locale'].'_restart']."</span>");
  }else{
    $object->newnotification($AppName,$language_screen[$_SESSION['locale'].'_settings_screen'],$language_screen[$_SESSION['locale'].'_notthemeerror']);
  }
}

$displayLink = '../../users/'.$_SESSION["loginuser"].'/settings/display.foc';
$AppLink = '../../users/'.$_SESSION["loginuser"].'/settings/AppWindow.foc';

if(isset($_GET['controlemode'])){
  $mode = $_GET['controlemode'];
  $size = $_GET['windowsize'];
  $saveSettings = "[WindowSetting]\nControlMode='$mode'\nWindowSize='$size'";
  file_put_contents($AppLink,$saveSettings);
}

if(file_exists($AppLink)){
  $getWindow = parse_ini_file($AppLink);
}else{
  $saveSettings = "[WindowSetting]\nControlMode='1'\nWindowSize='8'";
  file_put_contents($AppLink,$saveSettings);
  $getWindow = parse_ini_file($AppLink);
  unset($saveSettings);
}

if(isset($_GET['scale'])){
  $saveSettings = "[DisplaySetting]\nscale='".$_GET['scale']."'\nrotate='".$_GET['rotate']."'\nsmooth='".$_GET['smooth']."'\ncontrast='".$_GET['contrast']."'\nbrightness='".$_GET['brightness']."'\nsaturate='".$_GET['saturate']."'";
  file_put_contents($displayLink,$saveSettings);
}

if(is_file($displayLink)){
  $getObject = parse_ini_file($displayLink);
}
    ?>

<div id="tabssettings<?echo $AppID?>" style="display: inline-block; width: 100%;">
  <ul>
    <li><a href="#mainsettingtab<?echo $AppID?>"><?echo $language_screen[$_SESSION['locale'].'_displaytab']?></a></li>
    <li><a href="#themesettingtab<?echo $AppID?>"><?echo $language_screen[$_SESSION['locale'].'_themetab']?></a></li>
    <li><a href="#wallsettingtab<?echo $AppID?>"><?echo $language_screen[$_SESSION['locale'].'_walltab']?></a></li>
  </ul>

  <div id="mainsettingtab<?echo $AppID?>">

    <div style="border-bottom: 2px dashed #ccc; margin: 3px; ">
      <div style="font-size: 20px; font-weight: 900; text-transform: uppercase; padding: 10px 0;"><?echo $language_screen[$_SESSION['locale'].'_window_label']?></div>
        <div style="background: #e6e6e6; padding: 10px; margin: 10px 0;">
          <div style="font-size:20px; padding: 10px 0; font-weight:600; font-variant: all-small-caps;"><?echo $language_screen[$_SESSION['locale'].'_control_label']?></div>
          <label for="Mode2-<?echo $AppID?>"><?echo $language_screen[$_SESSION['locale'].'_left_label']?></label>
          <input class="radiogroup<?echo $AppID?>" type="radio" name="radio-<?echo $AppID?>" id="Mode2-<?echo $AppID?>" mode="2">
          <label for="Mode1-<?echo $AppID?>"><?echo $language_screen[$_SESSION['locale'].'_right_label']?></label>
          <input class="radiogroup<?echo $AppID?>" type="radio" name="radio-<?echo $AppID?>" id="Mode1-<?echo $AppID?>" mode="1">
          <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_windowsize_label']?>:</div>
          <div style="width:200px;" id="window-size<?echo $AppID?>">
            <div id="window-handle<?echo $AppID?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
          </div>
      </div>
    </div>

    <div style="border-bottom: 2px dashed #ccc; margin: 3px; ">
      <div style="font-size: 20px; font-weight: 900; text-transform: uppercase; padding: 10px 0;"><?echo $language_screen[$_SESSION['locale'].'_correct_label']?></div>
      <div style="background: #e6e6e6; padding: 10px; margin: 10px 0;">
        <div style="margin: 20px auto;">
          <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_contrast_label']?>:</div>
          <div style="width:200px;" id="contrast-filter<?echo $AppID?>">
            <div id="contrast-handle<?echo $AppID?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
          </div>
        </div>

        <div style="margin: 20px auto;">
          <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_brightness_label']?>:</div>
          <div style="width:200px;" id="brightness-filter<?echo $AppID?>">
            <div id="brightness-handle<?echo $AppID?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
          </div>
        </div>

        <div style="margin: 20px auto;">
          <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_saturate_label']?>:</div>
          <div style="width:200px;" id="saturate-filter<?echo $AppID?>">
            <div id="saturate-handle<?echo $AppID?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
          </div>
        </div>
      </div>
    </div>

    <div style="border-bottom: 2px dashed #ccc; margin: 3px; ">
      <div style="font-size: 20px; font-weight: 900; text-transform: uppercase; padding: 10px 0;"><?echo $language_screen[$_SESSION['locale'].'_display_label']?></div>
      <div style="background: #e6e6e6; padding: 10px; margin: 10px 0;">
        <div style="margin: 20px auto;">
          <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_scale_label']?>:</div>
          <div style="width:200px;" id="scale-display<?echo $AppID?>">
            <div id="scale-handle<?echo $AppID?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
          </div>
        </div>

        <div style="margin: 20px auto;">
          <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_rotate_label']?>:</div>
          <div style="width:200px;" id="rotate-display<?echo $AppID?>">
            <div id="rotate-handle<?echo $AppID?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
          </div>
        </div>

        <div style="margin: 20px auto;">
          <div style="padding: 10 0px;"><?echo $language_screen[$_SESSION['locale'].'_smooth_label']?>:</div>
          <div style="width:200px;" id="smooth-display<?echo $AppID?>">
            <div id="smooth-handle<?echo $AppID?>" style="width: auto; min-width:20px; text-align:center;" class="ui-slider-handle"></div>
          </div>
        </div>
      </div>
    </div>

    <div style="width: 100%; float: left;">
    <div class="ui-forest-button ui-forest-accept ui-forest-center" onClick="savedisplay<?echo $AppID?>()">
      <?echo $language_screen[$_SESSION['locale'].'_save_button']?>
    </div>

    <div class="ui-forest-button ui-forest-cancel ui-forest-center" onClick="resetdisplay<?echo $AppID?>()">
      <?echo $language_screen[$_SESSION['locale'].'_reset_button']?>
    </div>
  </div>

  </div>

  <div id="themesettingtab<?echo $AppID?>">
<?
$current_theme  = parse_ini_file('../../users/'.$_SESSION["loginuser"].'/settings/etc/theme.fth');
$current_theme  = $current_theme['Name'];
$dir2 = '../../core/design/themes/';
$d2 = dir($dir2);
chdir($d2->path2);

while (false !== ($entry2=$d2->read())) {
  $path2 = $d2->path2;
  $name2 = $entry2;
  if ($entry2!='.' && $entry2!='..'){
    $themeloadset=parse_ini_file('../../core/design/themes/'.$name2);
    if($current_theme == $themeloadset['Name']){
      $select_style  = 'border: 2px solid '.$themeloadset['draggablebackcolor'].';  box-shadow:0 0 5px '.$themeloadset['topbarbackcolor'].';';
      $select_text  = '<span style="font-size:10px;">('.$language_screen[$_SESSION['locale'].'_current_label'].')</span>';
    }
        echo(
          '<div id="'.$name2.'" class="ui-button ui-widget ui-corner-all ui-forest-blink" onClick="loadtheme'.$AppID.'(this);" style="-webkit-user-select:none; cursor:pointer; '.$select_style.' user-select:none; padding:5px; background-color:'.$themeloadset['backgroundcolor'].'; margin:5px; color:'.$themeloadset['backgroundfontcolor'].'; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; text-transform:uppercase; ">
          <div style="height:15%; background:'.$themeloadset['topbarbackcolor'].';"></div><div style="height:15%; background:'.$themeloadset['draggablebackcolor'].'; margin-bottom: 5px;"></div>'.$themeloadset['Name'].'<br>'.$select_text.'</div>
          ');
        $select_style  = '';
        $select_text  = '';
      }
    }
    $dir2->close;
?>
</div>

<div id="wallsettingtab<?echo $AppID?>">

  <div style="padding: 10px 0; border-bottom:1px solid #ccc;">
    <div style="font-size:20px; padding: 10px 0; font-weight:600; font-variant: all-small-caps;"><?echo $language_screen[$_SESSION['locale'].'_webwall_label']?></div>
    <label for="WallMode2-<?echo $AppID?>"><?echo $language_screen[$_SESSION['locale'].'_on_label']?></label>
    <input class="radiogroup<?echo $AppID?>" type="radio" name="wbradio-<?echo $AppID?>" id="WallMode2-<?echo $AppID?>" mode="2">
    <label for="WallMode1-<?echo $AppID?>"><?echo $language_screen[$_SESSION['locale'].'_off_label']?></label>
    <input class="radiogroup<?echo $AppID?>" type="radio" name="wbradio-<?echo $AppID?>" id="WallMode1-<?echo $AppID?>" mode="1">
  </div>
  <?
  $dir = '../../core/design/walls/';
  $d = dir($dir);
  chdir($d->path);
  echo(
    '<div id="clearwall" class="ui-button ui-widget ui-corner-all ui-forest-blink" onClick="clearwall'.$AppID.'();" style="-webkit-user-select:none; border:2px dashed #da362a; background-color:#ffcece; cursor:pointer; user-select:none; padding:5px; margin:5px; color:#000; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; ">
      <div style="padding-top:35%; font-variant:all-small-caps; font-weight:900; font-size:17px; color:#da362a;">'.$language_screen[$_SESSION['locale'].'_clear_label'].'</div>
    </div>'
  );
  while (false !== ($entry=$d->read())) {
  	$path = $d->path;
  	$name = $entry;
    $color = '#80abc6';
  	if ($entry != '.' && $entry != '..'){
  	   echo('<div id="'.$name.'" class="ui-button ui-widget ui-corner-all ui-forest-blink" onClick="loadwall'.$AppID.'(this);" style="-webkit-user-select:none; cursor:pointer; user-select:none; padding:5px; background-color:'.$color.'; margin:5px; color:#000; width:80px; height:80px; word-wrap:break-word; text-overflow:ellipsis; overflow:hidden; background-color:transparent;  background-image: url(../../system/core/design/walls/'.$name.'); background-size:cover;"></div>');
     }
   }
$dir->close;
  ?>
</div>
</div>
<?
$AppContainer->EndContainer();
?>
<script>

<?

// WebWall Mode
$AppContainer->Event(
	"WebWallMode",
	'object',
	$Folder,
	'personalization',
	array(
		'wbmode' => '"+object+"',
    'activetab' => '2'
	)
);

?>

$("#tabssettings<?echo $AppID?>").tabs();

//set active tab
  $(function(){
    $("#tabssettings<?echo $AppID?>").tabs({
      active: <?echo $activeTab?>
    });
  });

  ControlModeWall = "<?echo $wbmode?>";
  $("#WallMode"+ControlModeWall+"-<?echo $value.$AppID?>").prop("checked",true);

  $("input:radio[name='wbradio-<?echo $AppID?>']").change(
    function(){
    WebWallMode = $(this).attr('mode');
    WebWallMode<?echo $AppID?>(WebWallMode);
  }
  );

  ControlMode = "<?echo $getWindow['ControlMode'];?>";
  if(!ControlMode){
    ControlMode = '1';
  }
  $("#Mode"+ControlMode+"-<?echo $value.$AppID?>").prop("checked",true);
  $(".radiogroup<?echo $AppID?>").checkboxradio();
  var getMode;
  $("input:radio[name='radio-<?echo $AppID?>']").change(
    function(){
    getMode = $(this).attr('mode');
    if(getMode == '1'){
      $('.appwindowbutton-container').css({
        'float' : 'right',
        'display' : 'block'
      });
      $('.process-title').css({
        'float' : 'left',
        'flex-direction': 'initial'
      });
    }else{
      $('.appwindowbutton-container').css({
        'float' : 'left',
        'display' : 'flex'
      });
      $('.process-title').css({
        'float' : 'right',
        'display' : 'flex',
        'flex-direction': 'row-reverse'
      });
    }
  });

  wHandler = $("#window-handle<?echo $AppID?>");
  windowValue = '<?echo $getWindow['WindowSize'];?>'
  $("#window-size<?echo $AppID?>").slider({
  	value: windowValue,
  	min: 5,
  	max: 12,
  	step:1,
  	create: function(){
  		wHandler.text(windowValue);
  	},
  	slide: function(event, ui){
  		wHandler.text(ui.value);
  		windowValue = ui.value;
  		$('.dragwindow').css('padding',''+windowValue+' 0 '+windowValue+' 0');
  	}
  });

  cHandler = $("#contrast-handle<?echo $AppID?>");
  bHandler = $("#brightness-handle<?echo $AppID?>");
  sHandler = $("#saturate-handle<?echo $AppID?>");
  scaleHandler = $("#scale-handle<?echo $AppID?>");
  rotateHandler = $("#rotate-handle<?echo $AppID?>");
  smoothHandler = $("#smooth-handle<?echo $AppID?>");
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

  $("#contrast-filter<?echo $AppID?>").slider({
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

  $("#brightness-filter<?echo $AppID?>").slider({
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

  $("#saturate-filter<?echo $AppID?>").slider({
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

  $("#scale-display<?echo $AppID?>").slider({
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

  $("#rotate-display<?echo $AppID?>").slider({
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
        $("#rotate-display<?echo $AppID?>").slider('value',180);
        rotateHandler.text(ui.value);
        getDisplay();
      }

      if(rotateHandler.text <= -170 || ui.value <= -170){
        $("#rotate-display<?echo $AppID?>").slider('value',-180);
        rotateHandler.text(ui.value);
        getDisplay();
      }
    }
  });

  $("#smooth-display<?echo $AppID?>").slider({
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


$( "#restart" ).on( "click", function() {
  return location.href = 'os.php';
});

<?

// back button
$AppContainer->Event(
  "back",
  NULL,
  $Folder,
  'main'
);

// saveTheme
$AppContainer->Event(
	"loadtheme",
	'object',
	$Folder,
	'personalization',
	array(
		'theme' => '"+object.id+"',
    'activetab' => '"+$("#tabssettings'.$AppID.'").tabs(\'option\',\'active\')+"'
	)
);

// saveDisplay
$AppContainer->Event(
	"savedisplay",
	NULL,
	$Folder,
	'personalization',
	array(
		'scale' => '"+scaleValue+"',
  	'rotate' => '"+rotateValue+"',
  	'smooth' => '"+smoothValue+"',
  	'contrast' => '"+contrastValue+"',
  	'brightness' => '"+brightnessValue+"',
  	'saturate' => '"+saturateValue+"',
  	'controlemode' => '"+getMode+"',
  	'windowsize' => '"+windowValue+"',
	)
);

// load wall
$AppContainer->Event(
  "loadwall",
  'object',
  $Folder,
  'personalization',
  array(
    'wall' => '"+object+"',
    'activetab' => '"+$("#tabssettings'.$AppID.'").tabs(\'option\',\'active\')+"'
  ),
  'if(object != \'none\'){object = object.id;}',
  0
);
?>

function clearwall<?echo $AppID?>(){
  $('#background-wall').attr('src','data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=');
  loadwall<?echo $AppID?>('none');
}


function resetdisplay<?echo $AppID?>(){
    getMode = '1';
    $("#Mode1-<?echo $value.$AppID?>").prop("checked",true);
    $('.appwindowbutton-container').css({
      'float' : 'right',
      'display' : 'block'
    });
    $('.process-title').css({
      'float' : 'left',
      'flex-direction': 'initial'
    });
    $('.dragwindow').css('padding','8 0 8 0');
    windowValue = '8';
    scaleValue = '1';
    rotateValue = '0';
    smoothValue = '0.5';
    contrastValue = '1';
    brightnessValue = '1';
    saturateValue = '1';
    getFilter();
    getDisplay();
    $('.backgroundtheme').css('transition','all '+smoothValue+'s ease');
    savedisplay<?echo $AppID?>();
}
</script>
