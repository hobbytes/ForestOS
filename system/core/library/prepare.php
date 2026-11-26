<?
  class prepare{

/*---------load language---------*/
    function language()
    {
      global $language;
      $language  = parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/system/core/os.lang');
    }

/*---------load language---------*/

    function showversion()
    {
      $osinfo = parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/system/core/osinfo.foc', false);
      $os_version = $osinfo['codename'].' '.$osinfo['subversion']."\n";
      if(!isset($_SESSION)){
        session_start();
      }
      $_SESSION['os_version'] = $osinfo['subversion'];
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

      $forwardedFor = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';

      if(isset($_SESSION["is_authuser"]) && $_SESSION["is_authuser"] === true && isset($_SESSION["loginuser"]) && isset($_SESSION["CookieIsMine"]) && $_SESSION["CookieIsMine"] != md5($_SESSION["loginuser"].$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'].$forwardedFor)){
        $_SESSION = array();
        session_destroy();
        header("Location: ?exit=0");
      }

      $dir = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION["loginuser"].'/settings/';

      //  # get superuser
      $bd = new readbd;
      if (empty($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true) {
        /* гостю нечего знать про администратора: раньше сюда попадал admin */
        $_SESSION['superuser'] = '';
      } else {
        $bd->readglobal2("status", "forestusers", "login", $_SESSION["loginuser"]);

        if($getdata != 'superuser'){
          $bd->readglobal2("login", "forestusers", "status", 'superuser');
        }else{
          $getdata = $_SESSION["loginuser"];
        }

        $_SESSION['superuser'] = $getdata;
      }

      if($_SESSION["safemode"]  ==  'true'){
        $__u = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'];
        file_put_contents($__u.'/settings/notifications/MainNotificationFile.hdf', '');
        file_put_contents($__u.'/settings/autorun.foc', '');
        /* безопасный режим: никакого прежнего состояния — окна, раскладка,
           отложенная гибернация. Сессия одна, вход уже выбил остальные. */
        require_once $_SERVER['DOCUMENT_ROOT'].'/system/core/library/workspace.php';
        ForestWorkspace::clear((string)$_SESSION['loginuser']);
        @unlink($__u.'/settings/desktop-desktop.json');
        @unlink($__u.'/settings/desktop-mobile.json');
        @unlink($__u.'/settings/state.hdf');
        $_SESSION["safemode"] = 'false';
      }

      /* прежняя проверка по forest.hobbytes.com убрана: домен мёртв, а запрос
         блокировал страницу на время DNS-таймаута */
      $_SESSION['godmode'] = 'true';

      // # check lang
      $_SESSION['locale'] = file_get_contents($dir.'language.foc');
      if(empty($_SESSION['locale'])){
        $_SESSION['locale'] = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['superuser'].'/settings/language.foc');
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
        $_SESSION['is_mobile'] = true;
      }else{
        $click = 'dblclick';
        $top = '25%';
        $left = '25%';
        $maxwidth = '90%';
        $_SESSION['is_mobile'] = false;
      }

      $themeload = parse_ini_file("system/users/".$_SESSION["loginuser"]."/settings/etc/theme.fth");
      $themecolor = str_replace(';', '', $themeload['topbarbackcolor']);
      if(preg_match('%rgb%', $themeload['topbarbackcolor'])){
        $rgbtohex = explode(",", str_replace(array('rgba', 'rgb', '(', ')',' '), '', $themeload['topbarbackcolor']), 3);
        $themecolor = sprintf("#%02x%02x%02x", $rgbtohex[0], $rgbtohex[1], $rgbtohex[2]);
      }
      /* тёмная тема: атрибут ставим сразу на <html>, чтобы не было вспышки светлого */
      $__darkFile = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION["loginuser"].'/settings/etc/dark.foc';
      $__dark = is_file($__darkFile) && trim((string)@file_get_contents($__darkFile)) === '1';
      ?>
      <html<? if ($__dark) { echo ' data-fo-dark="1"'; } ?>>
      <head>
      <title>Forest OS</title>
      <link rel="apple-touch-icon" sizes="180x180" href="system/core/design/images/favicons/apple-touch-icon.png">
      <link rel="icon" type="image/png" href="system/core/design/images/favicons/favicon-32x32.png" sizes="32x32">
      <link rel="icon" type="image/png" href="system/core/design/images/favicons/favicon-16x16.png" sizes="16x16">
      <link rel="manifest" href="system/core/design/images/favicons/manifest.json">
      <link rel="mask-icon" href="system/core/design/images/favicons/safari-pinned-tab.svg" color="#3e495e">
      <meta name="theme-color" content="<? echo $themecolor ?>">
      <meta name="application-name" content="Forest OS">
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
      <meta name="analytics-s-page-tracking-data" content="Forest OS">
      <link rel="stylesheet" type="text/css" href="<? echo $hashfile->filehash('system/core/design/tokens.css')?>">
      <link rel="stylesheet" type="text/css" href="<? echo $hashfile->filehash('system/core/design/main.css')?>">
      <link rel="stylesheet" href="<? echo $hashfile->filehash('system/core/design/jquery-ui.css')?>">
      <link rel="stylesheet" type="text/css" href="<? echo $hashfile->filehash('system/core/design/shell.css')?>">
      <link rel="stylesheet" type="text/css" href="<? echo $hashfile->filehash('system/core/design/app-shell.css')?>">
      <?
        if(!(isset($_SESSION['is_authuser']) && $_SESSION['is_authuser'] === true)){
          ?>
            <link rel="stylesheet" type="text/css" href="<? echo $hashfile->filehash('system/core/design/login.css')?>">
          <?
        }
      ?>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery-1.12.4.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery-ui.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery.ui.touch-punch.min.js')?>"></script>
      <script src="<? echo $hashfile->filehash('system/core/library/js/jquery.mousewheel.min.js')?>"></script>
      </head>
      <?
      unset($themeload);

      // # check timezone
      $_SESSION['timezone'] = file_get_contents($dir.'timezone.foc');
      if(empty($_SESSION['timezone'])){
        $_SESSION['timezone'] = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['superuser'].'/settings/timezone.foc');
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
      global $auth, $mainwall, $hashfile, $prepare;
      $emptyImage = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
      if ($auth->isAuth())
    {
      $file_ = 'system/users/'.$_SESSION["loginuser"].'/settings/etc/wall.jpg';
      if (file_exists($file_)){
        $mainwall = $hashfile->filehash($file_);
      }else{
        $mainwall = $emptyImage;
      }
    }else{
      $file = 'system/core/design/images/webwall.jpg';
      $file_off = 'system/core/design/images/webwallOFF.foc';

      if(file_exists($file_off)){
        if(file_exists($file))
          {
            $filedate = date('dmyHis',filemtime($file));
            $datess = date_create_from_format('dmyHis',$filedate);
            $datess2 = date_format($datess,"dmyHis");
            $timelate = date_timestamp_get(date_modify($datess,'+2 days'));
            $timenow = time();
            if ($timenow>$timelate){
              $prepare->changewall($file);
              $mainwall = $hashfile->filehash($file);
            }else{
              $mainwall = $hashfile->filehash($file);
            }
        }else{
          $prepare->changewall($file);
          $mainwall = $hashfile->filehash($file);
        }
      }else{
        $mainwall = $emptyImage;
      }

    }
    }

/*---------load desktop---------*/
    function desktop($delclassname, $user = NULL)
    {
    global $name, $login, $hashfile, $click, $top, $left, $maxwidth;
    $id = 0;

    $__device = !empty($_SESSION['is_mobile']) ? 'mobile' : 'desktop';
    $__layoutFile = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/settings/desktop-'.$__device.'.json';
    $__layout = array();
    if (is_file($__layoutFile)) {
      $__j = json_decode((string)@file_get_contents($__layoutFile), true);
      if (is_array($__j)) { $__layout = $__j; }
    }
    $__hasLayout = !empty($__layout);

    if(!empty($user)){
      $login = $user;
      chdir($_SERVER['DOCUMENT_ROOT']);
    }

    foreach (glob($_SERVER['DOCUMENT_ROOT']."/system/users/$login/desktop/*.link") as $filenames)
    {
      $link = parse_ini_file($filenames);
      $ref  = $link['destination'];
      $name = $link['name'];
      $linkname = $link['linkname'];
      $file = $link['file'];
      $param  = $link['param'];
      $key  = $link['key'];
      $linkicon = NULL;
      if(!empty($link['icon'])){
        $linkicon = $_SERVER['DOCUMENT_ROOT'].'/'.$link['icon'];
      }
      $getExt = pathinfo($param);
      $extension = NULL;
      $appicon = str_replace(array('.', 'php', 'main', $_SERVER['DOCUMENT_ROOT']),'',$ref).'app.png';

      if (!is_file($appicon)){
        $appicon = './system/core/design/images/app.png';
      }

      if(!empty($getExt['extension'])){
        if(!preg_match("/\.(jpg|jpeg|png|gif|svg|bmp|webapp)$/i", $param) && $linkname != 'manifest.json'){ // print extenstion if !image
          $extension = '<div style="position:absolute; top:60px; left:0; right:0; cursor:default; color:#d05858; font-size:14px; font-weight:900;">'.$getExt['extension'].'</div>';
        }
      }


      if(!file_exists($appicon)){
        $appicon = NULL;
      }

      if(empty($linkicon)){
        $linkicon = $hashfile->filehash($appicon);
      }else{
        $linkicon = $hashfile->filehash('./'.$link['icon']);
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
      $__pos = isset($__layout[$filenames]) ? $__layout[$filenames] : null;
      $__style = '';
      if (is_array($__pos)) {
        $__style = ' style="position:absolute;left:'.(int)$__pos['x'].'px;top:'.(int)$__pos['y'].'px;"';
      }
      $__app = '';
      if ($classtrash === '' && preg_match('#system/apps/([^/]+)/#', $ref, $__m)) { $__app = $__m[1]; }
      /* файл: аккуратная страница с иконкой приложения, которое его открывает */
      $__filebadge = '';
      if ($key === 'defaultloader' && $param !== '' && $__app !== '' && @is_file($param)) {
        $linkicon = './system/core/design/images/file-doc.svg';
        $__filebadge = $hashfile->filehash('./system/apps/'.$__app.'/app.png');
        $extension = '';
      }
      $__badgeHtml = empty($__filebadge) ? '' : '<span class="filebadge" style="background-image:url(' . $__filebadge . ')"></span>';
      $__placed = is_array($__pos) ? ' fo-placed' : '';
      /* a folder shortcut can be pinned to the dock by dragging it down */
      $__folder = ($key === 'dir' && $param !== '') ? ' data-folder="' . htmlspecialchars($param) . '"' : '';
      ?>
      <div class="link<? echo $id ?> link<? echo $__placed ?>" link="<? echo $id ?>" title="Перетащите, чтобы переместить" d="<? echo htmlspecialchars($filenames) ?>" i="<? echo $id ?>" data-key="<? echo htmlspecialchars($filenames) ?>" data-app="<? echo htmlspecialchars($__app) ?>"<? echo $__folder ?><? echo $__style ?> <? echo $type ?>>
      <div id="linklog<? echo $id ?>" class="<? echo $delclassname ?>">
      <script>
      $( function() {
        $( "#<?echo 'icon'.$id ?>" ).click(function(){
        releaselink();
        $( "#<?echo 'icons'.$id ?>" ).css('z-index', '-3');
        var border_color = $('.topbartheme').css('background-color');
        $("#link_content<? echo $id ?>").css({
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
        $( "#<?echo 'icon'.$id ?>" ).dblclick(function(){$("#<?echo 'app'.$id ?>").show('drop',500)})
        } );

        $('.notificationclass').appendTo("#notification-container");
      </script>
      </div>
      <?
      $linkname = str_replace("_"," ",$linkname);
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
                <div class="iconimage" style="background-image: url('.$linkicon.');">'.$__badgeHtml.'
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
                    ?>
                    <script>
                    $(".trashdrop").droppable({
                      accept: ".ico",
                      drop: function(event, ui){
                        var del_file = ui.draggable.attr('d');
                        $.ajax({
                          type: "POST",
                          url: "system/core/functions/trash",
                          data: {
                             file_delete: del_file
                          }
                        }).done(function(o) {
                          $(".link"+ui.draggable.attr('i')).remove();
                      });
                      }
                    });
                    </script>
                    <?
                    echo '<script>window.FO_DESKTOP_DEVICE='.json_encode($__device).';window.FO_DESKTOP_HAS_LAYOUT='.($__hasLayout?'true':'false').';</script>';
                    echo '<script src="system/core/library/js/desktop-layout.js"></script>';
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

    if(!empty($content) && $_SESSION["safemode"] != "true"){
      $_SESSION['h_status'] = true;
      $_SESSION['appid']  = $content['last_app_id'];
      $key = $bd->readglobal2("password", "forestusers", "login", $login, true);
      echo $security->__decode($content['state'], $key);
      file_put_contents('system/users/'.$login.'/settings/state.hdf', '');
      $object->newnotification("Hibernation", $language[$_SESSION['locale'].'_hibernation_name'], $language[$_SESSION['locale'].'_hibernation_notification']."  <b>".$content['time_stamp']."</b>");
      ?>
      <script>
      var id = <? echo $content['last_app_id'] ?>;
      </script>
      <?
    }else{
      file_put_contents('system/users/'.$login.'/settings/state.hdf', '');
      $_SESSION['h_status'] = false;
    }
  }
  unset($content, $security, $key, $getdata);
}

/*---------topbar load---------*/
function topbar(){
  global $object, $login, $language;
  $settings_name = $language[$_SESSION['locale'].'_settings_menu'];
  $about_name = $language[$_SESSION['locale'].'_about_menu'];
  $exit_name = isset($language[$_SESSION['locale'].'_exit']) ? $language[$_SESSION['locale'].'_exit'] : 'Выход';

  /* подписи кнопок питания — из языкового файла, с заглавной буквы */
  $__pwLabel = function (string $key, string $fallback) use ($language) {
      $v = trim((string)($language[$_SESSION['locale'] . '_' . $key] ?? ''));
      if ($v === '') { $v = $fallback; }
      return mb_strtoupper(mb_substr($v, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($v, 1, null, 'UTF-8');
  };
  $__pwExit = $__pwLabel('exit', 'Выход');
  $__pwReload = $__pwLabel('reload', 'Перезагрузка');
  $__pwShutdown = $__pwLabel('shutdown', 'Выключение');

  $apps = $this->appcatalog($login, $language);
  ?>
  <div id="topbar" class="fo-bar topbartheme" style="display:none;">
    <div class="fo-bar-side fo-bar-left">
      <button type="button" id="fo-launcher-btn" class="fo-bar-btn" title="Приложения (Ctrl+K)" aria-label="Приложения">
        <span class="fo-ico-grid"></span>
      </button>
      <div id="fo-bar-workspaces" class="fo-ws" aria-hidden="true"></div>
      <div id="fo-bar-title" class="fo-bar-title">Forest OS</div>
    </div>
    <div class="fo-bar-center">
      <button type="button" id="fo-bar-clock" class="fo-bar-clock" title="Дата и время">
        <span id="time" class="fo-bar-time">--:--</span>
        <span class="fo-bar-date"><?echo $object->getDayRus().', '.date('d');?></span>
      </button>
    </div>
    <div class="fo-bar-side fo-bar-right">
      <div id="fastbuttons" class="fo-bar-group fo-hidden">
        <button type="button" id="hideall" class="fo-bar-btn" title="Свернуть все окна">−</button>
        <button type="button" id="closeall" class="fo-bar-btn" title="Закрыть все окна" onclick="$('.process').remove(); $('#fastbuttons').addClass('fo-hidden');">×</button>
      </div>
      <button type="button" id="notificationsbtn" class="fo-bar-btn fo-bar-bell not-btn" title="Уведомления">N</button>
      <button type="button" id="fo-mb-close" class="fo-bar-btn fo-bar-mbclose" title="Закрыть приложение">×</button>
      <button type="button" id="fo-node-btn" class="fo-bar-btn fo-bar-node" title="Node-режим (Ctrl+Shift+N)" onclick="if(window.FONode){window.FONode.toggle();}">&#9095;</button>
      <button type="button" id="fo-user-btn" class="fo-bar-user" title="<?echo str_replace('_',' ',$login)?>">
        <span class="fo-avatar"><?echo strtoupper(substr($login, 0, 1))?></span>
      </button>
    </div>
  </div>

  <div id="fo-usermenu" class="fo-popover fo-hidden" role="menu">
    <div class="fo-popover-head"><b><?echo str_replace('_',' ',$login)?></b><span>Администратор</span></div>
    <button type="button" class="fo-popover-item" data-act="accounts">Учётные записи</button>
    <button type="button" class="fo-popover-item" data-act="about"><?echo $about_name?></button>
    <button type="button" class="fo-popover-item fo-danger" data-act="logout"><?echo $exit_name?></button>
  </div>

  <div id="fo-launcher" class="fo-launcher" aria-hidden="true">
    <div class="fo-launcher-card" role="dialog" aria-modal="true" aria-label="Приложения">
      <div class="fo-launcher-search">
        <span class="fo-ico-search"></span>
        <input id="fo-launcher-input" type="text" placeholder="Поиск приложений и действий…" autocomplete="off" spellcheck="false">
        <kbd>esc</kbd>
      </div>
      <div id="fo-launcher-list" class="fo-launcher-list fo-scroll"></div>
      <div class="fo-launcher-foot">
        <span class="fo-launcher-hint">↑ ↓ — выбор · Enter — открыть</span>
        <div class="fo-launcher-power">
          <button type="button" class="fo-power" data-power="exit" title="<?echo htmlspecialchars($__pwExit)?>"><?echo htmlspecialchars($__pwExit)?></button>
          <button type="button" class="fo-power" data-power="reload" title="<?echo htmlspecialchars($__pwReload)?>"><?echo htmlspecialchars($__pwReload)?></button>
          <button type="button" class="fo-power" data-power="shutdown" title="<?echo htmlspecialchars($__pwShutdown)?>"><?echo htmlspecialchars($__pwShutdown)?></button>
        </div>
      </div>
    </div>
  </div>
  <script>
  window.FO_APPS = <? echo json_encode(array_values($apps), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  window.FO_LOGIN = <? echo json_encode($login); ?>;
  window.FO_I18N = <? echo json_encode(array(
      'exit' => $__pwExit,
      'reload' => $__pwReload,
      'shutdown' => $__pwShutdown,
  ), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  function hide_menu(){ $('#fo-launcher').removeClass('is-open').attr('aria-hidden','true'); }
  </script>
  <?
}

/*---------app catalog (launcher data)---------*/
function appcatalog($login, $language){
  $loc = $_SESSION['locale'];
  $doc = $_SERVER['DOCUMENT_ROOT'];
  $names = array(
    'Explorer'   => isset($language[$loc.'_explorer_menu']) ? $language[$loc.'_explorer_menu'] : 'Explorer',
    'Settings'   => isset($language[$loc.'_settings_menu']) ? $language[$loc.'_settings_menu'] : 'Settings',
    'Apps_House' => isset($language[$loc.'_store_menu']) ? $language[$loc.'_store_menu'] : 'Store',
    'update'     => 'Обновления'
  );
  $hints = array(
    'Explorer'   => 'Файлы и папки',
    'Settings'   => 'Параметры системы',
    'Apps_House' => 'Каталог приложений',
    'update'     => 'Центр обновлений'
  );
  $out = array();
  foreach (glob($doc.'/system/apps/*', GLOB_ONLYDIR) as $dir) {
    $app = basename($dir);
    if (!is_file($dir.'/main.php')) { continue; }
    if (!preg_match('/^[A-Za-z0-9_\-]+$/', $app)) { continue; }
    $icon = is_file($dir.'/app.png') ? 'system/apps/'.$app.'/app.png' : 'system/core/design/images/app.png';
    $out[] = array(
      'id'   => $app,
      'name' => isset($names[$app]) ? $names[$app] : $app,
      'hint' => isset($hints[$app]) ? $hints[$app] : 'Приложение',
      'icon' => $icon,
      'path' => 'system/apps/'.$app.'/main.php'
    );
  }
  $out[] = array(
    'id' => 'accounts', 'name' => 'Учётные записи', 'hint' => 'Пользователи системы',
    'icon' => 'system/apps/Settings/app.png', 'path' => 'system/apps/Settings/users.php',
    'param' => $login, 'key' => 'selectuser'
  );
  $out[] = array(
    'id' => 'about', 'name' => 'О системе', 'hint' => 'Версия и сведения',
    'icon' => 'system/core/design/images/forestosicon.png', 'path' => 'system/apps/Settings/about.php'
  );
  return $out;
}

/*---------appearance (accent + frame)---------*/
function appearance(){
  global $login;
  $file = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$login.'/settings/appearance.foc';
  $a = @parse_ini_file($file);
  if(!is_array($a)){ $a = array(); }
  $accent = isset($a['accent']) ? trim((string)$a['accent'], " ;") : '';
  if(!preg_match('/^#[0-9a-fA-F]{3,8}$/', $accent)){ $accent = ''; }
  $frame = !isset($a['frame']) || (string)$a['frame'] === '1';
  return array('accent' => $accent, 'frame' => $frame);
}

/*---------theme load---------*/
    function themeload()
    {
      global $login, $mobile;
      $themeload = parse_ini_file("system/users/$login/settings/etc/theme.fth");
      if(!is_array($themeload)){ $themeload = array(); }
      $__appearance = $this->appearance();
      $__accent = ($__appearance['accent'] !== '') ? $__appearance['accent'] : $this->accentcolor($themeload);
      $__chrome = trim((string)(isset($themeload['topbarbackcolor']) ? $themeload['topbarbackcolor'] : ''), " ;");
      $__chromeFg = trim((string)(isset($themeload['topbarfontcolor']) ? $themeload['topbarfontcolor'] : ''), " ;");
      $__menu = trim((string)(isset($themeload['menubackcolor']) ? $themeload['menubackcolor'] : ''), " ;");
      $__drag = trim((string)(isset($themeload['draggablebackcolor']) ? $themeload['draggablebackcolor'] : ''), " ;");
      $__dragFg = trim((string)(isset($themeload['draggablefontcolor']) ? $themeload['draggablefontcolor'] : ''), " ;");
      $__bgFg = trim((string)(isset($themeload['backgroundfontcolor']) ? $themeload['backgroundfontcolor'] : ''), " ;");
      $__shellBase = $this->islightcolor($__chrome) ? '#f2f3f2' : '#101312';
      if($__chrome === ''){ $__chrome = 'rgba(28,28,30,0.9)'; }
      if($__chromeFg === ''){ $__chromeFg = '#ffffff'; }
      if($__menu === ''){ $__menu = 'rgba(28,28,30,0.95)'; }
      echo '<style>';
      ?>
      :root
      {
        --fo-theme-topbar: <?echo $__chrome?>;
        --fo-theme-text: <?echo $__chromeFg?>;
        --fo-accent: <?echo $__accent?>;
        --fo-theme-menu: <?echo $__menu?>;
        --fo-shell-base: <?echo $__shellBase?>;
      }
      .topbartheme
      {
        background: <?echo $__chrome?>;
        color: <?echo $__chromeFg?>;
      }
      .menutheme
      {
        background: <?echo $__menu?>;
        color: <?echo trim((string)(isset($themeload['menufontcolor']) ? $themeload['menufontcolor'] : ''), " ;")?>;
      }
      .menulines{
        height: 2px;
        border:none;
        background: <?echo $__chrome?>;
        color: <?echo $__chrome?>;
        filter:contrast(0.9);
      }
      .backgroundtheme
      {
        background: <?echo trim((string)(isset($themeload['backgroundcolor']) ? $themeload['backgroundcolor'] : ''), " ;")?>;
      }
      .linktheme
      {
        color: <?echo $__bgFg?>;
      }
      .action-buttons{
        background: <?echo $__drag?>;
        color: <?echo $__dragFg?>;
      }

      .ui-forest-menu-labels:hover{
      	background: <?echo $__drag?>;
      }

      <?

      if(!empty($themeload['customCSS'])){
        echo $themeload['customCSS'];
      }

      /* dark mode: атрибут уже выставлен на <html> выше */

    }

    /* Pick an accent colour for the window chrome: explicit theme value first,
       otherwise a lively version of the theme's top bar colour. */
    function accentcolor($themeload){
      $raw = trim((string)(isset($themeload['accentcolor']) ? $themeload['accentcolor'] : ''), " ;");
      if($raw !== ''){
        $rgba = $this->hexorrgb($raw);
        if($rgba !== null){ return $rgba; }
      }
      $base = isset($themeload['topbarbackcolor']) ? $themeload['topbarbackcolor'] : '';
      $c = $this->rgbparts($base);
      if($c === null){
        $c = $this->rgbparts(isset($themeload['draggablebackcolor']) ? $themeload['draggablebackcolor'] : '');
      }
      if($c === null){ return '#6ea8fe'; }
      list($h, $s, $l) = $this->rgb2hsl($c[0], $c[1], $c[2]);
      if($s < 0.16){ return '#6ea8fe'; }
      $s = min(1, max(0.5, $s));
      $l = 0.62;
      $o = $this->hsl2rgb($h, $s, $l);
      return sprintf('#%02x%02x%02x', $o[0], $o[1], $o[2]);
    }

    function islightcolor($c){
      $p = $this->rgbparts($c);
      if($p === null){ return false; }
      $l = (0.299 * $p[0] + 0.587 * $p[1] + 0.114 * $p[2]) / 255;
      return $l > 0.6;
    }

    function rgbparts($c){
      $c = (string)$c;
      if(preg_match('/rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})/i', $c, $m)){
        return array((int)$m[1], (int)$m[2], (int)$m[3]);
      }
      if(preg_match('/#([0-9a-fA-F]{6})/', $c, $m)){
        return array(hexdec(substr($m[1],0,2)), hexdec(substr($m[1],2,2)), hexdec(substr($m[1],4,2)));
      }
      if(preg_match('/#([0-9a-fA-F]{3})\b/', $c, $m)){
        return array(hexdec($m[1][0].$m[1][0]), hexdec($m[1][1].$m[1][1]), hexdec($m[1][2].$m[1][2]));
      }
      return null;
    }

    function hexorrgb($c){
      $p = $this->rgbparts($c);
      if($p === null){ return null; }
      return sprintf('#%02x%02x%02x', max(0,min(255,$p[0])), max(0,min(255,$p[1])), max(0,min(255,$p[2])));
    }

    function rgb2hsl($r, $g, $b){
      $r /= 255; $g /= 255; $b /= 255;
      $max = max($r, $g, $b); $min = min($r, $g, $b);
      $l = ($max + $min) / 2;
      if($max == $min){ return array(0, 0, $l); }
      $d = $max - $min;
      $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
      if($max == $r){ $h = fmod(($g - $b) / $d, 6); }
      elseif($max == $g){ $h = ($b - $r) / $d + 2; }
      else { $h = ($r - $g) / $d + 4; }
      $h *= 60; if($h < 0){ $h += 360; }
      return array($h, $s, $l);
    }

    function hsl2rgb($h, $s, $l){
      $c = (1 - abs(2 * $l - 1)) * $s;
      $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
      $m = $l - $c / 2;
      if($h < 60){ $rgb = array($c, $x, 0); }
      elseif($h < 120){ $rgb = array($x, $c, 0); }
      elseif($h < 180){ $rgb = array(0, $c, $x); }
      elseif($h < 240){ $rgb = array(0, $x, $c); }
      elseif($h < 300){ $rgb = array($x, 0, $c); }
      else { $rgb = array($c, 0, $x); }
      return array((int)round(($rgb[0] + $m) * 255), (int)round(($rgb[1] + $m) * 255), (int)round(($rgb[2] + $m) * 255));
    }

function DisplaySettings(){
  global $login;
  $displaySettings = "system/users/$login/settings/display.foc";
  $appSettings = "system/users/$login/settings/AppWindow.foc";
  if($_SESSION["safemode"] != 'true'){
    /* window chrome metrics */
    $getAppSettings = @parse_ini_file($appSettings);
    if(!is_array($getAppSettings)){ $getAppSettings = array(); }
    $__btn = isset($getAppSettings['ButtonSize']) ? (int)$getAppSettings['ButtonSize'] : 12;
    if($__btn < 8){ $__btn = 8; }
    if($__btn > 18){ $__btn = 18; }
    $__size = isset($getAppSettings['WindowSize']) ? (int)$getAppSettings['WindowSize'] : 8;
    if($__size < 5){ $__size = 5; }
    if($__size > 12){ $__size = 12; }
    echo ':root{--fo-winbtn:'.$__btn.'px;--fo-winsize:'.$__size.';}';
    echo '</style>';
    $__appearance = $this->appearance();
    echo '<script>document.documentElement.setAttribute("data-fo-frame","'.($__appearance['frame'] ? 'accent' : 'neutral').'");</script>';
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
      $urlw='https://forest.hobbytes.com/media/os/loginwalls.php';
      $filew=file_get_contents($urlw);
      $arrayw=json_decode($filew,TRUE);
      if($arrayw!='')
      {
      $mainwall='https://forest.hobbytes.com/media/os/loginpage/'.$arrayw[rand(0,count($arrayw)-1)]['file'].'.jpg';
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

  $folder = '/system/core/';
  if(!isset($_SESSION)){
    session_start();
  }

?>

<script>
//Update desktop
function UpdateDesktop(){
  $(".desktop").remove();
  $("#desktops").load("/system/core/functions/UpdateDesktop.php", function(){
    SetTable();
    SetSelectors();
  });
}
</script>
