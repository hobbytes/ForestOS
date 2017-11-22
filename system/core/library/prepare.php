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
      global $hashfile,$getdata,$mobile,$infob,$click,$top,$left,$maxwidth;
      //  # get superuser
      $bd = new readbd;
      $bd->readglobal2("status","forestusers","login",$_SESSION["loginuser"]);
      if($getdata != 'superuser'){
        $bd->readglobal2("login","forestusers","status",superuser);
      }else{
        $getdata  = $_SESSION["loginuser"];
      }
      $_SESSION['superuser'] = $getdata;
      // # check lang
      $_SESSION['locale'] = file_get_contents('system/users/'.$_SESSION["loginuser"].'/settings/language.foc');
      if(empty($_SESSION['locale'])){
        $_SESSION['locale'] = file_get_contents('system/users/'.$_SESSION['superuser'].'/settings/language.foc');
        if(empty($_SESSION['locale'])){
        file_put_contents('system/users/'.$_SESSION["loginuser"].'/settings/language.foc','en');
        $_SESSION['locale'] = 'en';
      }
      }
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
      $ref  = $link['destination'];
      $name = $link['name'];
      $linkname = $link['linkname'];
      $file = $link['file'];
      $param  = $link['param'];
      $key  = $link['key'];
      $linkicon = $link['icon'];
      $appicon=str_replace(array('.','php','main'),'',$ref).'app.png';
      if (!is_file($appicon)){
        $appicon='system/core/design/images/app.png';
      }
      if(!file_exists($appicon)){
        $appicon='';
      }
      if($linkicon==''){
        $linkicon=$hashfile->filehash($appicon);
      }else{
        $linkicon=$hashfile->filehash('./'.$link['icon']);
      }
      ?>
      <div class="link<?echo $id;?>">
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
      }
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
            <div id="link'.$id.'" class="'.$delclassname.'" on'.$click.'="makeprocess('.$destination.','.$param.','.$key.','.$name.'); releaselink();">
              <div id=icons'.$id.' class="ui-widget-header ui-widget-content ico clickme'.$classtrash.'" d="'.$filenames.'" i="'.$id.'" style="padding:5px; z-index:-1000; height:auto; text-align:center; width:70px; position:relative; display:block; float:left;">
                <div style="background-color:transparent;  background-image: url('.$linkicon.'); background-size:cover; height:64px; width:64px; margin:auto; margin-top:17px; ">
                  <div id=icon'.$id.' style="width:100%; height:auto;">
                    <div id="link_content'.$id.'" class="linktheme">
                      '.$linkname.'
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
  global $login,  $getdata, $object, $security, $language;
  $file = 'system/users/'.$login.'/settings/state.hdf';
  if(file_exists($file)){
    $content = parse_ini_file($file);
    session_start();
    if(!empty($content) && $_SESSION["safemode"]!='true'){
      session_start();
      $_SESSION['appid']  = $content['last_app_id']-1;
      $bds= new readbd;
  		$bds->readglobalfunction(password,users,login,$login);
      $key=$getdata;
      echo $security->__decode($content['state'], $key);
      file_put_contents('system/users/'.$login.'/settings/state.hdf','');
      $object->newnotification("Hibernation",$language[$_SESSION['locale'].'_hibernation_name'],$language[$_SESSION['locale'].'_hibernation_notification']."  <b>".$content['time_stamp']."</b>");
    }else{
      file_put_contents('system/users/'.$login.'/settings/state.hdf','');
    }
  }
  unset($content,$bds,$security,$key,$getdata);
}

/*---------topbar load---------*/
function topbar(){
  global $object, $login, $language;
  $settings_name = $language[$_SESSION['locale'].'_settings_menu'];
  $store_name = $language[$_SESSION['locale'].'_store_menu'];
  $about_name = $language[$_SESSION['locale'].'_about_menu'];
  $explorer_name = $language[$_SESSION['locale'].'_explorer_menu'];
  ?>
  <div id="topbar" class="ui-widget-content topbartheme" style="display:none; z-index:9999; height:25px; padding-top:5px;">
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
    <div id="menu1" class="ui-forest" onmouseover="document.getElementById('aboutmenu').style.display='block';" onmouseout="document.getElementById('aboutmenu').style.display='none';" style="z-index:9999; user-select: none; cursor: default; text-align:center; width:50px; font-size:19px; padding:2px 0;">
      =
    </div>
  </div>
  <div id="aboutmenu" class="ui-widget-content menutheme" onmouseover="document.getElementById('aboutmenu').style.display='block';" onmouseout="document.getElementById('aboutmenu').style.display='none';" style="z-index:9999; user-select:none; display:none; text-align:justify; min-width:200px; max-width:300px; position:absolute; text-overflow:hidden; overflow:ellipsis; padding:14px 0 0 0;">
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
    <b class="ui-forest action_button" name="<?echo $language[$_SESSION['locale'].'_restart'];?>" onclick="return location.href = 'os.php'">R</b>
    <b class="ui-forest action_button" name="<?echo $language[$_SESSION['locale'].'_memoryrestart'];?>" onclick="hibernation('false')">S</b>
    <b class="ui-forest action_button" name="<?echo $language[$_SESSION['locale'].'_hibernation'];?>" onclick="hibernation('true')">H</b>
    <b class="ui-forest action_button" name="<?echo $language[$_SESSION['locale'].'_exit'];?>" onclick="return location.href = '?action=logout'">E</b>
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
      session_start();
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
  }
?>
