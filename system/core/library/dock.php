<?php
/**
 * Forest OS — док.
 *
 * Порядок иконок, разделители и закреплённые папки хранятся в одном файле
 * `system/users/<login>/settings/Dock/layout.json`. Старые закрепления
 * (файлы в Dock/A, Dock/B…) переносятся в него автоматически при первом
 * открытии, поэтому обновление ничего не ломает.
 *
 *   {"items":[{"t":"app","name":"Explorer"},{"t":"sep"},
 *             {"t":"folder","store":"documents/images"}]}
 *
 * item: app (имя приложения), sep (разделитель), folder (store — путь от
 * домашней папки или '@' + путь от корня сайта).
 */
class Dock {

  const LAYOUT = 'layout.json';

  function CreateNewDock(){

    if(!isset($_SESSION)){
      session_start();
    }

    if(!isset($_SESSION['loginuser'])){ return; }

    $doc   = self::docRoot();
    $login = (string)$_SESSION['loginuser'];
    $dir   = self::dir($doc, $login);
    if(!is_dir($dir)){ @mkdir($dir, 0777, true); }

    $items = self::readLayout($dir, $doc, $login);

    echo '<div class="dock-container topbartheme" data-dock="1">';

    foreach ($items as $it) {
      if ($it['t'] === 'app') {
        self::renderApp($it['name'], $doc);
      } elseif ($it['t'] === 'sep') {
        echo '<span class="dock-separator" data-sep="1" title="Разделитель"></span>';
      } elseif ($it['t'] === 'folder') {
        self::renderFolder($it['store'], $doc, $login);
      }
    }

    if(!$_SESSION['is_mobile']){
      echo '<script src="system/core/library/js/dock-scroll.js"></script>';
    }
    echo '</div>';
  }

  /* ---------------- отрисовка ---------------- */

  private static function renderApp(string $app, string $doc): void
  {
    $appJs = htmlspecialchars(json_encode($app), ENT_QUOTES);
    $action = "if(window.foDockClick){foDockClick($appJs);}else{makeprocess('"
      . $doc . "/system/apps/" . $app . "/main.php', $appJs, '', $appJs);}";
    echo '<div class="dock-icon" title="' . htmlspecialchars($app) . '" data-app="' . htmlspecialchars($app) . '"'
      . ' onClick="' . $action . '"'
      . ' style="background-image: url(./system/apps/' . htmlspecialchars($app) . '/app.png?h=1ca7108a11d44b16d38fce45ef19670b);">'
      . '<span class="dock-dot"></span></div>';
  }

  private static function renderFolder(string $store, string $doc, string $login): void
  {
    $abs = self::resolveStore($store, $doc, $login);
    if ($abs === null || !is_dir($abs)) { return; }
    $title = basename($abs);
    $isTrash = (strpos($abs, '/trash') !== false);
    $icon = $isTrash ? './system/apps/Explorer/assets/trashicon.png' : './system/apps/Explorer/assets/folderico.png';
    $absJs = htmlspecialchars(json_encode($abs), ENT_QUOTES);
    $titleJs = htmlspecialchars(json_encode($title), ENT_QUOTES);
    $action = "if(window.foDockFolder){foDockFolder($absJs, $titleJs);}else{makeprocess('system/apps/Explorer/main.php', $absJs, 'defaultloader', 'Explorer');}";
    echo '<div class="dock-icon dock-folder' . ($isTrash ? ' dock-trash' : '') . '"'
      . ' title="' . htmlspecialchars($title) . '"'
      . ' data-folder="' . htmlspecialchars($abs) . '"'
      . ' data-store="' . htmlspecialchars($store) . '"'
      . ' onClick="' . $action . '"'
      . ' style="background-image: url(' . $icon . ');"><span class="dock-dot"></span></div>';
  }

  /* ---------------- пути и layout ---------------- */

  public static function docRoot(): string
  {
    return rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
  }

  public static function dir(string $doc, string $login): string
  {
    return $doc . '/system/users/' . $login . '/settings/Dock/';
  }

  /** store → абсолютный путь (или null, если путь недопустим). */
  public static function resolveStore(string $store, string $doc, string $login): ?string
  {
    $store = str_replace('\\', '/', trim($store));
    if ($store === '' || strpos($store, '..') !== false) {
      return null;
    }
    if ($store[0] === '@') {
      $rel = ltrim(substr($store, 1), '/');
      return $rel === '' ? null : $doc . '/' . $rel;
    }
    return $doc . '/system/users/' . $login . '/' . ltrim($store, '/');
  }

  /** Путь (абсолютный, '@…' или относительный) → store. */
  public static function storeFromPath(string $path, string $doc, string $login): ?string
  {
    $path = str_replace('\\', '/', trim($path));
    if ($path === '' || strpos($path, '..') !== false) {
      return null;
    }
    $home = $doc . '/system/users/' . $login;
    if ($path[0] === '@') {
      return $path;
    }
    if (strpos($path . '/', $doc . '/') === 0) {
      $rel = trim(substr($path, strlen($doc)), '/');
      return $rel === '' ? null : '@' . $rel;
    }
    if (strpos($path . '/', $home . '/') === 0) {
      $rel = trim(substr($path, strlen($home)), '/');
      return $rel === '' ? null : $rel;
    }
    /* относительный путь: сначала домашняя папка, потом корень сайта */
    $rel = trim($path, '/');
    if ($rel === '') {
      return null;
    }
    if (is_dir($home . '/' . $rel)) {
      return $rel;
    }
    if (is_dir($doc . '/' . $rel)) {
      return '@' . $rel;
    }
    return $rel;
  }

  public static function layoutFile(string $dir): string
  {
    return rtrim($dir, '/') . '/' . self::LAYOUT;
  }

  public static function readLayout(string $dir, string $doc, string $login): array
  {
    $file = self::layoutFile($dir);
    if (is_file($file)) {
      $raw = (string)@file_get_contents($file);
      $j = json_decode($raw, true);
      if (is_array($j) && isset($j['items']) && is_array($j['items'])) {
        $items = self::normalize($j['items'], $doc, $login);
        if ($items !== $j['items']) {
          self::saveLayout($dir, $items);
        }
        return $items;
      }
    }
    $items = self::migrate($dir, $doc, $login);
    self::saveLayout($dir, $items);
    return $items;
  }

  public static function saveLayout(string $dir, array $items): bool
  {
    if (!is_dir($dir)) {
      @mkdir($dir, 0777, true);
    }
    $file = self::layoutFile($dir);
    $json = json_encode(array('items' => array_values($items)),
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
      return false;
    }
    $tmp = $file . '.tmp' . bin2hex(random_bytes(3));
    if (@file_put_contents($tmp, $json) === false) {
      return false;
    }
    @chmod($tmp, 0666);
    if (!@rename($tmp, $file)) {
      @unlink($tmp);
      return false;
    }
    return true;
  }

  /** Чистка: выкидываем несуществующие приложения/папки, дубли и лишние разделители. */
  public static function normalize(array $items, string $doc, string $login): array
  {
    $out = array();
    $seenApps = array();
    $seenStores = array();
    $prevSep = true; // ведущий разделитель не нужен
    foreach ($items as $it) {
      if (!is_array($it) || !isset($it['t'])) { continue; }
      if ($it['t'] === 'app') {
        $name = (string)($it['name'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $name)) { continue; }
        if (!is_file($doc . '/system/apps/' . $name . '/main.php')) { continue; }
        if (isset($seenApps[$name])) { continue; }
        $seenApps[$name] = true;
        $out[] = array('t' => 'app', 'name' => $name);
        $prevSep = false;
      } elseif ($it['t'] === 'folder') {
        $store = (string)($it['store'] ?? '');
        $abs = self::resolveStore($store, $doc, $login);
        if ($abs === null || !is_dir($abs)) { continue; }
        if (isset($seenStores[$store])) { continue; }
        $seenStores[$store] = true;
        $out[] = array('t' => 'folder', 'store' => $store);
        $prevSep = false;
      } elseif ($it['t'] === 'sep') {
        if ($prevSep) { continue; }
        $out[] = array('t' => 'sep');
        $prevSep = true;
      }
    }
    while ($out && end($out)['t'] === 'sep') {
      array_pop($out);
    }
    return $out;
  }

  /** Перенос старых закреплений (файлы в Dock/A, Dock/B…) в layout.json. */
  private static function migrate(string $dir, string $doc, string $login): array
  {
    $apps = array();
    $folders = array();
    $groups = glob(rtrim($dir, '/') . '/*', GLOB_ONLYDIR) ?: array();
    sort($groups);
    foreach ($groups as $g) {
      $files = glob($g . '/*') ?: array();
      sort($files);
      foreach ($files as $f) {
        if (!is_file($f)) { continue; }
        $name = basename($f);
        if (strpos($name, 'folder_') === 0) {
          $store = trim((string)@file_get_contents($f));
          if ($store !== '' && !in_array($store, $folders, true)) { $folders[] = $store; }
          continue;
        }
        if (is_file($doc . '/system/apps/' . $name . '/main.php') && !in_array($name, $apps, true)) {
          $apps[] = $name;
        }
      }
    }
    $items = array();
    foreach ($apps as $a) { $items[] = array('t' => 'app', 'name' => $a); }
    if ($folders) {
      if ($items) { $items[] = array('t' => 'sep'); }
      foreach ($folders as $s) { $items[] = array('t' => 'folder', 'store' => $s); }
    }
    return self::normalize($items, $doc, $login);
  }
}
?>
