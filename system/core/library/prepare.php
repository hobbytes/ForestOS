<?
  class prepare{

/*---------load language---------*/
    function language()
    {
      global $language;
      $language  = parse_ini_file('system/core/os.lang');
    }
/*---------load head---------*/
    public function start()
    {
      // # check lang
      $_SESSION['locale'] = file_get_contents('system/users/'.$_SESSION["loginuser"].'/settings/language.foc');
      if(empty($_SESSION['locale'])){
        file_put_contents('system/users/'.$_SESSION["loginuser"].'/settings/language.foc','en');
        $_SESSION['locale'] = 'en';
      }
      global $hashfile,$mobile,$infob,$click,$top,$left,$maxwidth;
      $infob->ismobile();
      if($mobile=='true'){$click='click'; $top='20px';$left='0px'; $maxwidth='100%';}else{$click='dblclick'; $top='25%';$left='25%'; $maxwidth='90%';}
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
      <link rel="stylesheet" type="text/css" href="<? echo $hashfile->filehash('system/core/design/main.css')?>">
      <link rel="stylesheet" href="<? echo $hashfile->filehash('system/core/design/jquery-ui.css')?>">
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery-1.12.4.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery-ui.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/time.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery.ui.touch-punch.min.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery.mousewheel.min.js')?>"></script>
      </head>
      <?
      if (function_exists('date_default_timezone_set'))
      date_default_timezone_set('Europe/Moscow');
    }

/*---------load wall---------*/
    function wall(){
      global $auth,$mainwall,$hashfile,$prepare;
      if ($auth->isAuth())
    {
      $file_ = 'system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg';
      if (file_exists($file_)){
        $mainwall = $hashfile->filehash($file_);
        $mainwall = 'background: url('.$mainwall.') 100% 100% no-repeat fixed;';
      }else{
        $mainwall = '';
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

      $mainwall = 'background: url('.$mainwall.') 100% 100% no-repeat fixed;';
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
      $ref=$link['destination'];$name=$link['name'];$linkname=$link['linkname'];$file=$link['file'];$param=$link['param'];$key=$link['key']; $linkicon=$link['icon'];
      $appicon=str_replace(array('.','php','main'),'',$ref).'app.png';
      if (!is_file($appicon)){
        $appicon='system/core/design/images/app.png';
      }
      if(!file_exists($appicon)){$appicon='';}
      if($linkicon==''){$linkicon=$hashfile->filehash($appicon);}else{$linkicon=$hashfile->filehash('./'.$link['icon']);}
      ?>

      <div id="linklog<?echo $id;?>" class="<?echo $delclassname;?>">
      <script>
      $( function() {
        $( "#<?echo 'icon'.$id.'';?>" ).click(function(){
          releaselink();
          var border_color = $('.action-buttons').css('background-color');
          $("#link_content<?echo $id?>").css({
            'white-space' : 'pre-line',
            'background-color' : 'rgba(177,207,228,0.3)',
            'border' : '1px solid ' + border_color
          });
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
      $classtrash = '';
      if(eregi($login.'/trash',$param)){
        $classtrash = ' trashdrop';
      }
        $namejs="'".$name."'";$filejs="'".$file."'";$paramjs="'".$param."'";$keyjs="'".$key."'";
            echo'<div id="link'.$id.'" class="'.$delclassname.'" on'.$click.'="makeprocess('.$namejs.','.$filejs.','.$paramjs.','.$keyjs.'); releaselink();"><div id=icons'.$id.' class="ui-widget-header ui-widget-content ico clickme'.$classtrash.'" d="'.$filenames.'" style="padding:5px; z-index:-1000; height:auto; text-align:center; width:70px; position:relative; display:block; float:left;"><div style="background-color:transparent;  background-image: url('.$linkicon.'); background-size:cover; height:64px; width:64px; margin:auto; margin-top:17px; ">';
            echo '<div id=icon'.$id.' style="width:100%; height:auto;">';
            echo '<div id="link_content'.$id.'" class="linktheme">';
            echo $linkname.'</div></div></div></div></div>';
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
  global $login,  $getdata, $object, $security, $language;
  $file = 'system/users/'.$login.'/settings/state.hdf';
  if(file_exists($file)){
    $content = parse_ini_file($file);
    if(!empty($content)){
      session_start();
      $_SESSION['appid']  = $content['last_app_id']-1;
      $bds= new readbd;
  		$bds->readglobalfunction(password,users,login,$login);
      $key=$getdata;
      echo $security->__decode($content['state'], $key);
      file_put_contents('system/users/'.$login.'/settings/state.hdf','');
      $object->newnotification("Hibernation",$language[$_SESSION['locale'].'_hibernation_name'],$language[$_SESSION['locale'].'_hibernation_notification']."  <b>".$content['time_stamp']."</b>");
    }
  }
  unset($content,$bds,$security,$key,$getdata);
}

/*---------topbar load---------*/
function topbar(){
  global $object, $login, $language;
  ?>
  <div id="topbar" class="ui-widget-content topbartheme" style="display:none; z-index:9999; height:22px; padding-top:4px;">
    <span id="hideall" class="topbaractbtn ui-forest" style="cursor:pointer; display:none; background-color:#37a22e; color:#fff; width:12px; float:right; text-align:center; width:15px; margin-right: 8px; font-family:monospace; padding:2px 0;">
      -
    </span>
    <span id="closeall" class="topbaractbtn ui-forest" style="cursor:pointer; display:none; background-color:#ed2020; color:#fff; float:right; text-align:center; width:15px; font-family:monospace; padding:2px 0;" onclick="$('.process').remove(); $('.topbaractbtn').css('display','none');">
      x
    </span>
    <div class="date " style="float:right; font-size:15px; padding-right:10px; user-select: none; cursor: default;">
      <?php echo $object->getDayRus().' '.date('d').',';?>
      <span id="time"></span>
    </div>
    <div id="notificationsbtn" class="ui-forest" style="float:right; font-size: 11px; margin-right: 10px; padding: 1px; user-select: none; border: 2px outset #fff; border-radius: 4px; cursor: default;">
      N
    </div>
    <script type="text/javascript">
      showTime();
    </script>
    <div id="menu1" class="ui-forest" onmouseover="document.getElementById('aboutmenu').style.display='block';" onmouseout="document.getElementById('aboutmenu').style.display='none';" style="z-index:9999; user-select: none; cursor: default; text-align:center; width:50px; font-size:19px; ">
      =
    </div>
  </div>
  <div id="aboutmenu" class="ui-widget-content menutheme" onmouseover="document.getElementById('aboutmenu').style.display='block';" onmouseout="document.getElementById('aboutmenu').style.display='none';" style="z-index:9999; user-select:none; display:none; text-align:justify; min-width:200px; max-width:300px; position:absolute; text-overflow:hidden; overflow:ellipsis; padding:14px 0 0 0;">
  <span style="text-transform:uppercase; cursor:pointer;  padding:5px;" onclick="makeprocess('Settings','users','<?echo $login;?>','selectuser'); document.getElementById('aboutmenu').style.display='none';">
    <?echo $login;?>
  </span>
  <hr class="menulines">
  <span style="cursor:pointer; padding:5px;" onclick="makeprocess('Explorer','main','',''); document.getElementById('aboutmenu').style.display='none';">
    <?echo $language[$_SESSION['locale'].'_explorer_menu']?>
  </span>
  <hr class="menulines">
  <span style="cursor:pointer; padding:5px;" onclick="makeprocess('Settings','main','',''); document.getElementById('aboutmenu').style.display='none';">
    <?echo $language[$_SESSION['locale'].'_settings_menu']?>
  </span>
  <hr class="menulines">
  <span style="cursor:pointer; padding:5px;" onclick="makeprocess('Apps_House','main','',''); document.getElementById('aboutmenu').style.display='none';">
    <?echo $language[$_SESSION['locale'].'_store_menu']?>
  </span>
  <hr class="menulines">
  <span style="cursor:pointer; padding:5px;" onclick="makeprocess('Settings','about','',''); document.getElementById('aboutmenu').style.display='none';">
    <?echo $language[$_SESSION['locale'].'_about_menu']?>
  </span>
    <div class="action-buttons" style="text-align:center; margin-top: 14px; padding:14px 0; filter:hue-rotate(8deg);">
    <span style="font-size:26px; cursor:default; width:26px;">
    <b class="ui-forest" style="border:2px solid; cursor:pointer; padding:0 6px; margin:3px; border-radius:5px;" onclick="return location.href = 'os.php'">R</b>
    <b class="ui-forest" style="border:2px solid; cursor:pointer; padding:0 6px; margin:3px; border-radius:5px;" onclick="hibernation('false')">M</b>
    <b class="ui-forest" style="border:2px solid; cursor:pointer; padding:0 6px; margin:3px; border-radius:5px;" onclick="hibernation('true')">H</b>
    <b class="ui-forest" style="border:2px solid; cursor:pointer; padding:0 6px; margin:3px; border-radius:5px;" onclick="return location.href = '?action=logout'">E</b>
  </span>
  </div>
  </div>
  <?
}
/*---------theme load---------*/
    function themeload()
    {
      global $login;
      $themeload=parse_ini_file("system/users/$login/settings/etc/theme.fth");
      ?>
      <style>
      .dragwindow
      {
        background-color: <?echo $themeload['draggablebackcolor'];?>;
        color: <?echo $themeload['draggablefontcolor'];?>;
      }
      .topbartheme
      {
        background-color: <?echo $themeload['topbarbackcolor'];?>;
        color: <?echo $themeload['topbarfontcolor'];?>;
      }
      .menutheme
      {
        background-color: <?echo $themeload['menubackcolor'];?>;
        color: <?echo $themeload['menufontcolor'];?>;
      }
      .menulines{
        height: 2px;
        border:none;
        background-color: <?echo $themeload['topbarbackcolor'];?>;
        color: <?echo $themeload['topbarbackcolor'];?>;
        filter:contrast(0.9);
      }
      .backgroundtheme
      {
        background-color: <?echo $themeload['backgroundcolor'];?>;
      }
      .linktheme
      {
        color: <?echo $themeload['backgroundfontcolor'];?>;
      }
      .dragwindowtoggle
      {
        color: <?echo $themeload['topbarfontcolor'];?>;
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
      </style>

      <?

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
      $content  = file_get_contents('system/users/'.$login.'/settings/autorun.foc');
      if($content){
        $array  = explode(",",$content);
        foreach ($array as $value){
          ?>
          <script>
          makeprocess2('system/apps/<?echo $value?>/main.php','','');
          </script>
          <?
        }
      }
    }
  }
?>
