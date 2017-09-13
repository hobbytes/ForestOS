<?
  class prepare{

/*---------load head---------*/
    function start()
    {
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
    }

/*---------load wall---------*/
    function wall(){
      global $auth,$mainwall,$hashfile,$prepare;
      if ($auth->isAuth())
    {
        $mainwall=$hashfile->filehash('system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg');
    }
    else
    {
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
        $( "#<?echo 'icon'.$id.'';?>" ).mouseover(function(){$(this).css('background-color','rgba(192,192,192,0.3)')});
        $( "#<?echo 'icon'.$id.'';?>" ).mouseout(function(){$(this).css('background-color','transparent')});
        $( "#<?echo 'icon'.$id.'';?>" ).dblclick(function(){$("#<?echo 'app'.$id.'';?>").show('drop',500)})
        } );
      </script>
      </div>
      <?
      $linkname=str_replace("_"," ",$linkname);
        $namejs="'".$name."'";$filejs="'".$file."'";$paramjs="'".$param."'";$keyjs="'".$key."'";
            echo'<div id="link'.$id.'" class="'.$delclassname.'" on'.$click.'="makeprocess('.$namejs.','.$filejs.','.$paramjs.','.$keyjs.');"><div id=icons'.$id.' class="ui-widget-header ui-widget-content ico clickme" style="z-index:-1000; height:auto; text-align:center; width:70px; position:relative; display:block; float:left;"><div style="background-color:transparent;  background-image: url('.$linkicon.'); background-size:cover; height:64px; width:64px; margin:auto; margin-top:17px; ">';
            echo '<div id=icon'.$id.' style="width:100%; height:auto;">';
            echo '<div class="linktheme" style="white-space: pre-wrap; padding-top: 100%; text-overflow:ellipsis; cursor:default; overflow:hidden; font-size:13px; text-shadow:0px 0px 4px rgba(0,0,0,0.9);" >';
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

/*---------topbar load---------*/
function topbar(){
  global $object;
  ?>
  <div id="topbar" class="ui-widget-content topbartheme" style="display:none; z-index:9999; height:22px; padding-top:4px;">
    <span id="hideall" class="topbaractbtn ui-forest" style="cursor:pointer; display:none; background-color:#5ca556; color:#fff; width:12px; float:right; text-align:center; width:15px; margin-right: 8px;">
      -
    </span>
    <span id="closeall" class="topbaractbtn ui-forest" style="cursor:pointer; display:none; background-color:#bf5a5a; color:#fff; float:right; text-align:center; width:15px;" onclick="$('.process').remove(); $('.topbaractbtn').css('display','none');">
      x
    </span>
    <div class="date " style="float:right; font-size:15px; padding-right:10px; user-select: none; cursor: default;">
      <?php echo $object->getDayRus().' '.date('d').',';?>
      <span id="time"></span>
    </div>
    <div id="notificationsbtn" class="ui-forest" style="float:right; font-size: 11px; margin-right: 10px; padding: 1px; user-select: none; border: 2px solid #fff; border-radius: 4px; cursor: default;">
      N
    </div>
    <script type="text/javascript">
      showTime();
    </script>
    <div id="menu1" class="ui-forest" onmouseover="document.getElementById('aboutmenu').style.display='block';" onmouseout="document.getElementById('aboutmenu').style.display='none';" style="z-index:9999; user-select: none; cursor: default; text-align:center; width:50px; font-size:19px; ">
      =
    </div>
  </div>
  <div id="aboutmenu" class="ui-widget-content menutheme" onmouseover="document.getElementById('aboutmenu').style.display='block';" onmouseout="document.getElementById('aboutmenu').style.display='none';" style="z-index:9999; user-select:none; display:none; text-align:justify; width:150px; max-width:300px; position:absolute; text-overflow:hidden; overflow:ellipsis; padding:5px;">
  <span style="text-transform:uppercase; cursor:pointer;" onclick="makeprocess('Settings','users','<?echo $login;?>','selectuser');">
    <?echo $login;?>
  </span>
  <hr class="menulines">
  <span style="cursor:pointer;" onclick="makeprocess('Explorer','main','',''); document.getElementById('aboutmenu').style.display='none';">
    Проводник
  </span>
  <hr class="menulines">
  <span style="cursor:pointer;" onclick="makeprocess('Settings','main','',''); document.getElementById('aboutmenu').style.display='none';">
    Параметры
  </span>
  <hr class="menulines">
  <span style="cursor:pointer;" onclick="makeprocess('Apps_House','main','',''); document.getElementById('aboutmenu').style.display='none';">
    Магазин
  </span>
  <hr class="menulines">
  <span style="cursor:pointer;" onclick="makeprocess('Settings','about','',''); document.getElementById('aboutmenu').style.display='none';">
    О системе
  </span>
  <hr class="menulines">
  <b>
    <span style="cursor:pointer;" onclick="return location.href = 'os.php'">
      Перезагрузка
    </span>
  </b>
  <hr class="menulines">
  <b>
    <span style="cursor:pointer;" onclick="return location.href = '?action=logout'">
      Выйти
    </span>
  </b>
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
