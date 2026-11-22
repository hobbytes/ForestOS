<?php

class Dock {

  function CreateNewDock(){

    if(!isset($_SESSION)){
      session_start();
    }

    if(!isset($_SESSION['loginuser'])){ return; }

    $dir_ = $_SERVER['DOCUMENT_ROOT'];
    $login = (string)$_SESSION['loginuser'];

    $dir = $dir_.'/system/users/'.$login.'/settings/Dock/';
    if(!is_dir($dir))
    {
      mkdir($dir);
      if(!is_dir($dir.'A/')){
        mkdir($dir.'A/');
        file_put_contents($dir.'A/Explorer', "");
        file_put_contents($dir.'A/Settings', "");
        file_put_contents($dir.'A/Apps_House', "");
      }
    }

    echo '<div class="dock-container topbartheme">';

    $groups = glob($dir.'*', GLOB_ONLYDIR);
    sort($groups);
    $renderedGroups = 0;

    foreach ($groups as $group) {

      $apps = array();
      $folders = array();

      foreach (glob($group.'/*') as $object) {
        $info = pathinfo($object);
        $name = $info['filename'];

        if (is_file($dir_."/system/apps/$name/main.php")) {
          if (!in_array($name, $apps, true)) { $apps[] = $name; }
          continue;
        }

        /* закреплённая папка: содержимое — путь '@system/...' или путь от домашней папки */
        if (strpos($name, 'folder_') === 0) {
          $raw = trim((string)@file_get_contents($object));
          if ($raw === '') { continue; }
          $abs = $this->resolveFolder($raw, $dir_, $login);
          if ($abs === null || !is_dir($abs)) { continue; }
          $folders[] = array('raw' => $raw, 'abs' => $abs, 'title' => basename($abs));
        }
      }

      if (!$apps && !$folders) { continue; }

      if ($renderedGroups > 0) { echo '<span class="dock-separator"></span>'; }
      $renderedGroups++;

      foreach ($apps as $app) {
        $Action = "if(window.foDockClick){foDockClick('$app');}else{makeprocess('$dir_/system/apps/$app/main.php', '$app', '', '$app');}";
        echo '<div class="dock-icon" title="'.$app.'" data-app="'.$app.'" onClick="'.$Action.'" style="background-image: url(./system/apps/'.$app.'/app.png?h=1ca7108a11d44b16d38fce45ef19670b);"><span class="dock-dot"></span></div>';
      }

      if ($folders) {
        echo '<span class="dock-separator"></span>';
        foreach ($folders as $folder) {
          $title = $folder['title'];
          $abs = $folder['abs'];
          $isTrash = (strpos($abs, '/trash') !== false);
          $icon = $isTrash
            ? './system/apps/Explorer/assets/trashicon.png'
            : './system/apps/Explorer/assets/folderico.png';
          $absJs = htmlspecialchars(json_encode($abs), ENT_QUOTES);
          $titleJs = htmlspecialchars(json_encode($title), ENT_QUOTES);
          $action = "if(window.foDockFolder){foDockFolder($absJs, $titleJs);}else{makeprocess('system/apps/Explorer/main.php', $absJs, 'defaultloader', 'Explorer');}";
          echo '<div class="dock-icon dock-folder'.($isTrash ? ' dock-trash' : '').'" title="'.htmlspecialchars($title).'" data-folder="'.htmlspecialchars($abs).'" onClick="'.$action.'" style="background-image: url('.$icon.');"><span class="dock-dot"></span></div>';
        }
      }
    }

    if(!$_SESSION['is_mobile']){
      echo '<script src="system/core/library/js/dock-scroll.js"></script>';
    }
    echo '</div>';
  }

  /* путь закреплённой папки: '@system/...' — от корня сайта, иначе от домашней папки */
  private function resolveFolder($raw, $docroot, $login)
  {
    $raw = str_replace('\\', '/', $raw);
    if ($raw !== '' && $raw[0] === '@') {
      $rel = ltrim(substr($raw, 1), '/');
      return $rel === '' ? null : $docroot . '/' . $rel;
    }
    $raw = trim($raw, '/');
    return $raw === '' ? null : $docroot . '/system/users/' . $login . '/' . $raw;
  }
}
?>
