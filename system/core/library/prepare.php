<?
  class prepare{

/*---------load language---------*/
    function language()
    {
      global $language;
      $language  = parse_ini_file('system/core/os.lang');
    }

/*---------load language---------*/

    function showversion()
    {
      $osinfo = parse_ini_file('system/core/osinfo.foc', false);
      $os_version = $osinfo['codename'].' '.$osinfo['subversion']."\n";
      ?>
<!--
   ____                 __    ____  ____
  / __/__  _______ ___ / /_  / __ \/ __/
 / _// _ \/ __/ -_|_-</ __/ / /_/ /\ \
/_/  \___/_/  \__/___/\__/  \____/___/
          <?echo $os_version?>
-->
      <?
    }

/*---------load head---------*/
    public function start()
    {
      global $hashfile, $getdata, $mobile, $infob, $click, $top, $left, $maxwidth, $bd;

      if(!isset($_SESSION)){
        session_start();
      }

      $dir = 'system/users/'.$_SESSION["loginuser"].'/settings/';

      //  # get superuser
      $bd = new readbd;
      $bd->readglobal2("status","forestusers","login",$_SESSION["loginuser"]);

      if($getdata != 'superuser'){
        $bd->readglobal2("login","forestusers","status",superuser);
      }else{
        $getdata = $_SESSION["loginuser"];
      }

      $_SESSION['superuser'] = $getdata;

      if($_SESSION["safemode"]  ==  'true'){
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/settings/notifications/MainNotificationFile.hdf','');
        $_SESSION["safemode"] = 'false';
      }

      if(isset($_SESSION["loginuser"])){
        $pwd = $bd->readglobal2("password", "forestusers", "login", $_SESSION["loginuser"], true);
        $fuid = $bd->readglobal2("fuid", "forestusers", "login", $_SESSION["loginuser"], true);
        $doc_root = $_SERVER['DOCUMENT_ROOT'];
        $hash = md5($fuid.$doc_root.$pwd);
        $_SESSION['godmode'] = file_get_contents("http://forest.hobbytes.com/media/os/ubase/checkgodmode?userhash=$hash");
        unset($pwd, $fuid, $doc_root, $hash);
      }

      // # check lang
      $_SESSION['locale'] = file_get_contents($dir.'language.foc');
      if(empty($_SESSION['locale'])){
        $_SESSION['locale'] = file_get_contents('system/users/'.$_SESSION['superuser'].'/settings/language.foc');
        if(empty($_SESSION['locale'])){
          file_put_contents($dir.'language.foc','en');
          $_SESSION['locale'] = 'en';
        }
      }

      $infob->ismobile();
      if($mobile == 'true'){
        $click = 'click';
        $top = '20px';
        $left = '0px';
        $maxwidth = '100%';
      }else{
        $click = 'dblclick';
        $top = '25%';
        $left = '25%';
        $maxwidth = '90%';
      }
      ?>
      <html>
      <head>
      <title>Forest OS</title>
      <link rel="apple-touch-icon" sizes="180x180" href="system/core/design/images/favicons/apple-touch-icon.png">
      <link rel="icon" type="image/png" href="system/core/design/images/favicons/favicon-32x32.png" sizes="32x32">
      <link rel="icon" type="image/png" href="system/core/design/images/favicons/favicon-16x16.png" sizes="16x16">
      <link rel="manifest" href="system/core/design/images/favicons/manifest.json">
      <link rel="mask-icon" href="system/core/design/images/favicons/safari-pinned-tab.svg" color="#3e495e">
      <meta name="theme-color" content="#ffffff">
      <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
      <meta name="apple-mobile-web-app-title" content="Forest OS">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="mobile-web-app-capable" content="yes">
      <meta name="author" content="Vyacheslav Gorodilov">
      <meta name="designer" content="Vyacheslav Gorodilov">
      <meta name="reply-to" content="contact@hobbytes.com">
      <meta name="pagename" content="Forest OS">
      <meta name="description" content="Forest OS - web shell">
      <meta name="copyright" content="Hobbytes">
      <link rel="stylesheet" type="text/css" href="<? echo $hashfile->filehash('system/core/design/main.css')?>">
      <link rel="stylesheet" href="<? echo $hashfile->filehash('system/core/design/jquery-ui.css')?>">
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery-1.12.4.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery-ui.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery.ui.touch-punch.min.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery.mousewheel.min.js')?>"></script>
      </head>
      <?
      // # check timezone
      $_SESSION['timezone'] = file_get_contents($dir.'timezone.foc');
      if(empty($_SESSION['timezone'])){
        $_SESSION['timezone'] = file_get_contents('system/users/'.$_SESSION['superuser'].'/settings/timezone.foc');
        if(empty($_SESSION['timezone'])){
        file_put_contents($dir.'timezone.foc','Europe/Moscow');
        $_SESSION['timezone'] = 'Europe/Moscow';
      }
      }
      $timezone = $_SESSION['timezone'];
      date_default_timezone_set("$timezone");
    }

/*---------load wall---------*/
    function wall(){
      global $auth,$mainwall,$hashfile,$prepare;
      if ($auth->isAuth())
    {
      $file_ = 'system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg';
      if (file_exists($file_)){
        $mainwall = $hashfile->filehash($file_);
      }else{
        $mainwall = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
      }
    }else{
      $file='system/core/design/images/webwall.jpg';
      if (file_exists($file))
      {
          $filedate=date('dmyHis',filemtime($file));
          $datess=date_create_from_format('dmyHis',$filedate);
          $datess2=date_format($datess,"dmyHis");
          $timelate= date_timestamp_get(date_modify($datess,'+2 days'));
          $timenow = time();
          if ($timenow>$timelate)
          {
            $prepare->changewall($file);
            $mainwall=$hashfile->filehash($file);
          }
          else
          {
            $mainwall=$hashfile->filehash($file);
          }
      }
      else
      {
        $prepare->changewall($file);
        $mainwall=$hashfile->filehash($file);
      }

    }
    }

/*---------load desktop---------*/
    function desktop($delclassname)
    {
    global $name,$login,$hashfile,$click,$top,$left,$maxwidth;
    $id=0;
    foreach (glob("system/users/$login/desktop/*.link") as $filenames)
    {
      $link=parse_ini_file($filenames);
      $ref  = $link['destination'];
      $name = $link['name'];
      $linkname = $link['linkname'];
      $file = $link['file'];
      $param  = $link['param'];
      $key  = $link['key'];
      $linkicon = $link['icon'];
      $getExt = pathinfo($param);
      $extension = '';
      $appicon=str_replace(array('.','php','main'),'',$ref).'app.png';
      if (!is_file($appicon)){
        $appicon='system/core/design/images/app.png';
      }
      if(!empty($getExt['extension'])){
        if(!preg_match("/\.(jpg|jpeg|png|gif|bmp)$/i",$param)){ // print extenstion if !image
          $extension = '<div style="position:absolute; top:60px; left:0; right:0; cursor:default; color:#d05858; font-size:14px; font-weight:900;">'.$getExt['extension'].'</div>';
        }
      }
      if(!file_exists($appicon)){
        $appicon='';
      }
      if($linkicon==''){
        $linkicon=$hashfile->filehash($appicon);
      }else{
        $linkicon=$hashfile->filehash('./'.$link['icon']);
      }
      $classtrash = '';
      if(preg_match("/$login\/trash/",$param)){
        $type = ' type="trash" ';
        $linkicon = './system/apps/Explorer/assets/trashicon.png';
        $classtrash = ' trashdrop';
        ?>
        <style>
        .ui-droppable-hover{
          transform: scale(0.7);
          transition: all 0.2s ease;
        }
        </style>
        <?
      }else{
        $type = '';
      }
      ?>
      <div class="link<?echo $id;?> link" <?echo $type?>>
      <div id="linklog<?echo $id;?>" class="<?echo $delclassname;?>">
      <script>
      $( function() {
        $( "#<?echo 'icon'.$id.'';?>" ).click(function(){
        releaselink();
        var border_color = $('.action-buttons').css('background-color');
        $("#link_content<?echo $id?>").css({
          'height' : 'auto',
          'white-space' : 'pre-line',
          'background-color' : 'rgba(177,207,228,0.3)',
          'border' : '1px solid ' + border_color,
          'border-radius':'4px'
        });
      });
        $( "#background-wall" ).click(function(){
          releaselink();
        });
        $( "#<?echo 'icon'.$id.'';?>" ).mouseover(function(){
          $(this).css('background-color'  , 'rgba(192,192,192,0.3)');
        });
        $( "#<?echo 'icon'.$id.'';?>" ).mouseout(function(){
          $(this).css('background-color'  , 'transparent');
        });
        $( "#<?echo 'icon'.$id.'';?>" ).dblclick(function(){$("#<?echo 'app'.$id.'';?>").show('drop',500)})
        } );
      </script>
      </div>
      <?
      $linkname=str_replace("_"," ",$linkname);
        if(!preg_match('/.php/',$ref)){
          $destination  = $ref.$file.'.php';
        }else{
          $destination = $ref;
        }
        $destination = htmlspecialchars("'$destination'");
        $param  =  htmlspecialchars("'$param'");
        $key  = htmlspecialchars("'$key'");
        $name = htmlspecialchars("'$name'");
            echo'
            <div id="link'.$id.'" class="'.$delclassname.'" on'.$click.'="makeprocess('.$destination.','.$param.','.$key.','.str_replace(' ','_',$name).'); releaselink();">
              <div id=icons'.$id.' class="ui-widget-header ui-widget-content ico clickme'.$classtrash.'" d="'.$filenames.'" i="'.$id.'">
                <div class="iconimage" style="background-image: url('.$linkicon.');">
                  <div id=icon'.$id.' style="width:100%; height:auto;">
                    '.$extension.'
                    <div id="link_content'.$id.'" class="linktheme">
                      '.$linkname.'
                      </div>
                      </div>
                      </div>
                      </div>
                      </div>
                      </div>';
                      $id++;
          }
    }

/*---------welcomescreen load---------*/
function welcomescreen(){
  ?>
  <div class="welcomescreen">
    <img src="system/core/design/images/forestosicon.png">
  </div>
  <?
}

/*---------check and load hibernation---------*/
function hibernation(){
  global $login,  $getdata, $object, $security, $language, $bd;
  $file = 'system/users/'.$login.'/settings/state.hdf';

  if(file_exists($file)){
    $content = parse_ini_file($file);

    if(!isset($_SESSION)){
      session_start();
    }

    if(!empty($content) && $_SESSION["safemode"]!='true'){
      $_SESSION['appid']  = $content['last_app_id']-1;
  		$bd->readglobalfunction(password,users,login,$login);
      $key = $getdata;
      echo $security->__decode($content['state'], $key);
      file_put_contents('system/users/'.$login.'/settings/state.hdf','');
      $object->newnotification("Hibernation",$language[$_SESSION['locale'].'_hibernation_name'],$language[$_SESSION['locale'].'_hibernation_notification']."  <b>".$content['time_stamp']."</b>");
    }else{
      file_put_contents('system/users/'.$login.'/settings/state.hdf','');
    }
  }
  unset($content,$security,$key,$getdata);
}

/*---------topbar load---------*/
function topbar(){
  global $object, $login, $language;
  $settings_name = $language[$_SESSION['locale'].'_settings_menu'];
  $store_name = $language[$_SESSION['locale'].'_store_menu'];
  $about_name = $language[$_SESSION['locale'].'_about_menu'];
  $explorer_name = $language[$_SESSION['locale'].'_explorer_menu'];
  ?>
  <div id="topbar" class="ui-widget-content topbartheme" style="display:none; z-index:9999; height:auto; padding-top:5px;">
    <span id="hideall" class="topbaractbtn ui-forest-blink" style="cursor:default; display:none; background-color:#37a22e; color:#fff; width:12px; float:right; text-align:center; width:20px; margin-right: 8px; font-family:monospace; padding:3px 0; border-radius:0px 4px 4px 0px;">
      -
    </span>
    <span id="closeall" class="topbaractbtn ui-forest-blink" style="cursor:default; display:none; background-color:#ed2020; color:#fff; float:right; text-align:center; width:20px; font-family:monospace; padding:3px 0; border-radius:4px 0px 0px 4px;" onclick="$('.process').remove(); $('.topbaractbtn').css('display','none');">
      x
    </span>
    <div class="date " style="float:right; font-size:15px; padding-right:10px; user-select: none; cursor: default;">
      <?php echo $object->getDayRus().' '.date('d').',';?>
      <span id="time"></span>
    </div>
    <div id="notificationsbtn" class="ui-forest-blink not-btn" style="float:right; font-size: 11px; margin-right: 10px; padding: 1px; user-select: none; border: 2px solid #fff; border-radius: 4px; cursor: default; color:#fff; filter:hue-rotate(90deg);">
      N
    </div>
    <div id="menu1" class="ui-forest" onmouseover="$('#aboutmenu').css('display','block')" onmouseout="$('#aboutmenu').css('display','none')" style="z-index:9999; user-select: none; cursor: default; text-align:center; width:50px; font-size:19px; padding:2px 0;">
      =
    </div>
  </div>
  <div id="aboutmenu" class="ui-widget-content menutheme" onmouseover="$('#aboutmenu').css('display','block')" onmouseout="$('#aboutmenu').css('display','none')" style="z-index:9999; user-select:none; display:none; text-align:justify; min-width:200px; max-width:300px; position:absolute; text-overflow:hidden; overflow:ellipsis; padding:14px 0 0 0;">
  <span style="text-transform:uppercase; cursor:pointer;  padding:5px;" onclick="makeprocess('system/apps/Settings/users.php','<?echo $login;?>','selectuser','<?echo $settings_name?>'); hide_menu();">
    <?echo str_replace('_',' ',$login);?>
  </span>
  <hr class="menulines">
  <span style="cursor:pointer; padding:5px;" onclick="makeprocess('system/apps/Explorer/main.php','','','<?echo $explorer_name?>'); hide_menu();">
    <?echo $explorer_name?>
  </span>
  <hr class="menulines">
  <span style="cursor:pointer; padding:5px;" onclick="makeprocess('system/apps/Settings/main.php','','','<?echo $settings_name?>'); hide_menu();">
    <?echo $settings_name?>
  </span>
  <hr class="menulines">
  <span style="cursor:pointer; padding:5px;" onclick="makeprocess('system/apps/Apps_House/main.php','','','<?echo $store_name?>'); hide_menu();">
    <?echo $store_name?>
  </span>
  <hr class="menulines">
  <span style="cursor:pointer; padding:5px;" onclick="makeprocess('system/apps/Settings/about.php','','','<?echo str_replace(' ','_',$about_name)?>'); hide_menu();">
    <?echo $about_name?>
  </span>
    <div class="action-buttons" style="text-align:center; margin-top:14px; padding:20px 0px 14px; filter:hue-rotate(8deg);">
    <span style="font-size:26px; cursor:default; width:26px;">
    <b class="ui-forest action_button" name="<?echo $language[$_SESSION['locale'].'_restart'];?>" onclick="SaveNotification(); return location.href = 'os.php'">R</b>
    <b class="ui-forest action_button" name="<?echo $language[$_SESSION['locale'].'_memoryrestart'];?>" onclick="SaveNotification(); hibernation('false')">S</b>
    <b class="ui-forest action_button" name="<?echo $language[$_SESSION['locale'].'_hibernation'];?>" onclick="SaveNotification(); hibernation('true')">H</b>
    <b class="ui-forest action_button" name="<?echo $language[$_SESSION['locale'].'_exit'];?>" onclick="SaveNotification(); return location.href = '?action=logout'">E</b>
  </span>
  <div id="buttons_label" style="opacity:0; height:0; font-variant: all-petite-caps; font-weight:600; transition: all 0.2s ease; font-size:14px; padding:10 0 0; letter-spacing: 3px;"></div>
  </div>
  </div>
  <script>
  function hide_menu(){
    $("#aboutmenu").css('display','none');
  }
  $(".action_button").on( "mouseover", function() {
    $("#buttons_label").css("opacity", "1");
    $("#buttons_label").css("height", "100%");
    $("#buttons_label").text($(this).attr("name"));
  });
  $(".action_button").on( "mouseout", function() {
  $("#buttons_label").css("opacity", "0");
  $("#buttons_label").css("height", "0");
  });
  </script>
  <?
}
/*---------theme load---------*/
    function themeload()
    {
      global $login, $mobile;
      $themeload = parse_ini_file("system/users/$login/settings/etc/theme.fth");
      echo '<style>';
      ?>
      .dragwindow
      {
        background: <?echo $themeload['draggablebackcolor'];?>;
        color: <?echo $themeload['draggablefontcolor'];?>;
      }
      .topbartheme
      {
        background: <?echo $themeload['topbarbackcolor'];?>;
        color: <?echo $themeload['topbarfontcolor'];?>;
      }
      .menutheme
      {
        background: <?echo $themeload['menubackcolor'];?>;
        color: <?echo $themeload['menufontcolor'];?>;
      }
      .menulines{
        height: 2px;
        border:none;
        background: <?echo $themeload['topbarbackcolor'];?>;
        color: <?echo $themeload['topbarbackcolor'];?>;
        filter:contrast(0.9);
      }
      .backgroundtheme
      {
        background: <?echo $themeload['backgroundcolor'];?>;
      }
      .linktheme
      {
        color: <?echo $themeload['backgroundfontcolor'];?>;
      }
      .windowborder
      {
        border: 3px solid <?echo $themeload['draggablebackcolor'];?>;
        border-radius: 6px 6px 8px 8px;
        border-top: 0;
      }
      .action-buttons{
        background: <?echo $themeload['draggablebackcolor'];?>;
        color: <?echo $themeload['draggablefontcolor'];?>;
      }

      <?

      if($mobile == 'true'){
        echo '
        .appwindowbutton
        {
        	margin-right: 13px;
        	font-size: 14px;
        	padding: 4px 7px;
        	border-radius: 4px;
        }
        ';
      }

    }

function DisplaySettings(){
  global $login;
  $displaySettings = "system/users/$login/settings/display.foc";
  $appSettings = "system/users/$login/settings/AppWindow.foc";
  if($_SESSION["safemode"] != 'true'){
    /* app title direction */
    $getAppSettings = parse_ini_file($appSettings);
    if(isset($getAppSettings['WindowSize'])){
      $size_w = $getAppSettings['WindowSize'];
      echo '
      .dragwindow{
        padding: '.$size_w.' 0 '.$size_w.' 0;
      }';
    }else{
      echo '
      .dragwindow{
        padding: 8 0 8 0;
      }';
    }
      if($getAppSettings['ControlMode'] == '2'){
        echo '
        .appwindowbutton-container{
          float: left;
          display: flex;
        }
        .process-title{
          float: right;
          display: flex;
          flex-direction: row-reverse;
        }';
      }elseif(empty($getAppSettings['ControlMode']) || !intval($getAppSettings['ControlMode']) || $getAppSettings['ControlMode'] == '1'){
        echo '
        .appwindowbutton-container{
          float: right;
        }
        .process-title{
          float: left;
        }';
      }
    echo '</style>';
    /* display settings */
  if(file_exists($displaySettings)){
    $getSettings = parse_ini_file($displaySettings);
    if(!empty($getSettings['scale'])){
      ?>
      <script>
        $(document).ready(function(){
          var scaleValue = '<?echo $getSettings['scale'];?>';
          var rotateValue = '<?echo $getSettings['rotate'];?>';
          var smoothValue = '<?echo $getSettings['smooth'];?>';
          var contrastValue = '<?echo $getSettings['contrast'];?>';
          var brightnessValue = '<?echo $getSettings['brightness'];?>';
          var saturateValue = '<?echo $getSettings['saturate'];?>';

          var valueFilter = 'contrast('+contrastValue+') brightness('+brightnessValue+') saturate('+saturateValue+')';
          $('.backgroundtheme').css('filter',valueFilter);

          var valueDisplay = 'rotate('+rotateValue+'deg) scale('+scaleValue+','+scaleValue+')';
          $('.backgroundtheme').css('transform',valueDisplay);

          $('.backgroundtheme').css('transition','all '+smoothValue+'s ease');
        });
      </script>
      <?
    }
  }
}else{
  unlink($displaySettings);
}
}

/*---------changewall function---------*/
    function changewall($file)
    {
      $urlw='http://forest.hobbytes.com/media/os/loginwalls.php';
      $filew=file_get_contents($urlw);
      $arrayw=json_decode($filew,TRUE);
      if($arrayw!='')
      {
      $mainwall='http://forest.hobbytes.com/media/os/loginpage/'.$arrayw[rand(0,count($arrayw)-1)]['file'].'.jpg';
      $ch=curl_init($mainwall);
       $fp=fopen($file,'wb');
       curl_setopt($ch, CURLOPT_FILE,$fp);
       curl_setopt($ch, CURLOPT_HEADER,0);
       curl_exec($ch);
       curl_close($ch);
       fclose($fp);
      }
    }

    function autorun(){
      global $login;
      if(!isset($_SESSION)){
        session_start();
      }
      $content  = file_get_contents('system/users/'.$login.'/settings/autorun.foc');
      if($content && $_SESSION["safemode"]!='true'){
        echo $_SESSION["safemode"];
        $array  = explode(",",$content);
        foreach ($array as $value){
          ?>
          <script>
          makeprocess('system/apps/<?echo $value?>/main.php','','','<?echo $value?>');
          </script>
          <?
        }
      }else{
        file_put_contents('system/users/'.$login.'/settings/autorun.foc','');
      }
      $_SESSION['safemode'] = 'false';
    }

    public function beacon(){
      global $bd, $login, $language;
      $fuid = $bd->readglobal2("fuid", "forestusers", "login", $login, true);
      $password = $bd->readglobal2("password", "forestusers", "login", $login, true);
      $token = md5($fuid.$_SERVER['DOCUMENT_ROOT'].$password);
      $check = file_get_contents('http://forest.hobbytes.com/media/os/ubase/lastseen.php?token='.$token.'&user='.$login);
      if($check == 'true'){
        $message = '<div style="position:absolute; color:#fff; z-index:9999; background:linear-gradient(to right, #ff416c, #ff4b2b); padding: 10px 0; width:100%; text-align:center;">'.$language[$_SESSION['locale'].'_error_370'].'<div>';
        exit($message);
      }
    }

  }

  $folder = $_SERVER['DOCUMENT_ROOT'].'/system/core/';
  if(!isset($_SESSION)){
    session_start();
  }

?>

<script>
//Save Notification Function
function SaveNotification(){
  var content = $("#notification-container").html();
  $.ajax({
    type: "POST",
    url: "<?echo $folder.'functions/NotificationSaver'?>",
    data: {
       login:"<?echo $_SESSION['loginuser']?>",
       body:content
    },
    success: function(datas){
      //console.log(datas);
    }
  });
}

//Clear Notification Function
function NotificationClear(){
  $("#notification-container").html('');
  SaveNotification();
  $("#notificationsbtn").css({'border':'2px solid #fff','background-color':'rgba(0,0,0,0)'});
}

</script>
