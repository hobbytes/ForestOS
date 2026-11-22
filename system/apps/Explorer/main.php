<?php
/* ==========================================================================
   Forest OS — Explorer 2.0 (Finder-style)
   --------------------------------------------------------------------------
   Full file manager for system/users/<login>:
     navigation, icons/list, multi-select, new file/folder, rename,
     copy/cut/paste, move to trash / delete permanently / empty trash,
     zip/unzip, upload (multi + drag&drop), download, desktop shortcut,
     properties, open-with, preview.
   Every path is jailed to the user home; state changes require a CSRF token.
   ========================================================================== */

ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

$DOC   = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$login = isset($_SESSION['loginuser']) ? (string)$_SESSION['loginuser'] : '';
$isAuth = isset($_SESSION['is_authuser']) && $_SESSION['is_authuser'] === true;

$AppName = isset($_GET['appname']) ? preg_replace('/[^A-Za-z0-9_]/', '', (string)$_GET['appname']) : 'Explorer';
$AppID   = isset($_GET['appid']) ? preg_replace('/[^A-Za-z0-9_]/', '', (string)$_GET['appid']) : '0';
$mobile  = isset($_GET['mobile']) ? (string)$_GET['mobile'] : 'false';

$explorerMode = isset($_GET['explorermode']) ? preg_replace('/[^A-Za-z0-9_]/', '', (string)$_GET['explorermode']) : '';
$callback = isset($_GET['callback']) ? preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$_GET['callback']) : '';
$defaultDirectory = isset($_GET['defaultdirectory']) ? (string)$_GET['defaultdirectory'] : '';

$ENDPOINT = 'system/apps/Explorer/main.php';

$locale = isset($_SESSION['locale']) ? (string)$_SESSION['locale'] : 'ru';
$L = ($locale === 'en') ? array(
    'favorites' => 'Favorites', 'home' => 'Home', 'desktop' => 'Desktop',
    'documents' => 'Documents', 'downloads' => 'Downloads', 'trash' => 'Trash',
    'open' => 'Open', 'open_with' => 'Open With', 'rename' => 'Rename',
    'new_folder' => 'New Folder', 'new_file' => 'New File', 'copy' => 'Copy',
    'cut' => 'Cut', 'paste' => 'Paste', 'compress' => 'Compress',
    'extract' => 'Extract', 'shortcut' => 'Make Alias', 'info' => 'Get Info',
    'download' => 'Download', 'move_trash' => 'Move to Trash',
    'delete_forever' => 'Delete Immediately', 'empty_trash' => 'Empty Trash',
    'upload' => 'Upload', 'back' => 'Back', 'forward' => 'Forward',
    'icons' => 'Icons', 'list' => 'List', 'search' => 'Search',
    'empty' => 'This folder is empty', 'items' => 'items', 'free' => 'free',
    'name' => 'Name', 'date' => 'Date Modified', 'size' => 'Size', 'kind' => 'Kind',
    'folder' => 'Folder', 'file' => 'File', 'cancel' => 'Cancel', 'ok' => 'OK',
    'create' => 'Create', 'save' => 'Save', 'close' => 'Close', 'delete' => 'Delete',
    'select_file' => 'Choose File', 'select' => 'Select',
    'session' => 'Your session has expired. Please sign in again.',
    'err_path' => 'That path is not available', 'err_name' => 'Invalid name',
    'err_exists' => 'Already exists', 'err_perm' => 'Cannot write here',
    'err_token' => 'Session expired, please reopen the window',
    'clipboard_empty' => 'Clipboard is empty', 'done' => 'Done',
    'confirm_trash' => 'Move to Trash?', 'confirm_delete' => 'Delete permanently?',
    'confirm_empty' => 'Empty the Trash? This cannot be undone.',
) : array(
    'favorites' => 'Избранное', 'home' => 'Домой', 'desktop' => 'Рабочий стол',
    'documents' => 'Документы', 'downloads' => 'Загрузки', 'trash' => 'Корзина',
    'open' => 'Открыть', 'open_with' => 'Открыть с помощью', 'rename' => 'Переименовать',
    'new_folder' => 'Новая папка', 'new_file' => 'Новый файл', 'copy' => 'Копировать',
    'cut' => 'Вырезать', 'paste' => 'Вставить', 'compress' => 'Сжать в ZIP',
    'extract' => 'Распаковать', 'shortcut' => 'Создать ярлык', 'info' => 'Свойства',
    'download' => 'Скачать', 'move_trash' => 'Переместить в корзину',
    'delete_forever' => 'Удалить окончательно', 'empty_trash' => 'Очистить корзину',
    'upload' => 'Загрузить', 'back' => 'Назад', 'forward' => 'Вперёд',
    'icons' => 'Значки', 'list' => 'Список', 'search' => 'Поиск',
    'empty' => 'Папка пуста', 'items' => 'объектов', 'free' => 'свободно',
    'name' => 'Имя', 'date' => 'Дата изменения', 'size' => 'Размер', 'kind' => 'Тип',
    'folder' => 'Папка', 'file' => 'Файл', 'cancel' => 'Отмена', 'ok' => 'ОК',
    'create' => 'Создать', 'save' => 'Сохранить', 'close' => 'Закрыть', 'delete' => 'Удалить',
    'select_file' => 'Выбрать файл', 'select' => 'Выбрать',
    'session' => 'Сессия истекла. Войдите заново.',
    'err_path' => 'Недоступный путь', 'err_name' => 'Недопустимое имя',
    'err_exists' => 'Уже существует', 'err_perm' => 'Нет прав на запись',
    'err_token' => 'Сессия устарела, обновите окно',
    'clipboard_empty' => 'Буфер обмена пуст', 'done' => 'Готово',
    'confirm_trash' => 'Переместить в корзину?', 'confirm_delete' => 'Удалить окончательно?',
    'confirm_empty' => 'Очистить корзину? Это действие необратимо.',
);

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fo_json($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fo_clean_name($name) {
    $name = (string)$name;
    $name = str_replace(array('/', '\\', "\0"), '', $name);
    $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
    if ($clean !== null) {
        $name = $clean;
    }
    $name = trim($name);
    if ($name === '' || $name === '.' || $name === '..') {
        return '';
    }
    if (mb_substr($name, 0, 1, 'UTF-8') === '.') {
        return '';
    }
    if (mb_strlen($name, 'UTF-8') > 180) {
        $name = mb_substr($name, 0, 180, 'UTF-8');
    }
    return $name;
}

function fo_resolve($home, $requested, $allowSystem = false) {
    $doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $home = str_replace('\\', '/', $home);
    $system = $doc . '/system';
    $r = str_replace('\\', '/', (string)$requested);
    if ($r === '' || $r === 'os' || $r === '/os') {
        return $home;
    }
    if (strpos($r, $doc) === 0) {
        $r = substr($r, strlen($doc));
    }
    $r = ltrim($r, '/');
    if ($r === '') {
        return $home;
    }
    foreach (explode('/', $r) as $seg) {
        if ($seg === '' || $seg === '.') {
            continue;
        }
        if ($seg === '..' || $seg[0] === '.') {
            return null;
        }
    }
    $real = realpath($doc . '/' . $r);
    if ($real === false) {
        return null;
    }
    $real = str_replace('\\', '/', $real);
    if ($real === $home || strpos($real . '/', $home . '/') === 0) {
        return $real;
    }
    if ($allowSystem && ($real === $system || strpos($real . '/', $system . '/') === 0)) {
        return $real;
    }
    return null;
}

/* True when $path lives inside the user's home (write operations allowed). */
function fo_in_home($path, $home) {
    $path = str_replace('\\', '/', (string)$path);
    $home = rtrim(str_replace('\\', '/', (string)$home), '/');
    return $path === $home || strpos($path . '/', $home . '/') === 0;
}

function fo_rel($path, $home) {
    $path = str_replace('\\', '/', $path);
    $home = str_replace('\\', '/', $home);
    if ($path === $home) {
        return '';
    }
    if (strpos($path, $home . '/') === 0) {
        return substr($path, strlen($home) + 1);
    }
    return '';
}

function fo_size($bytes) {
    $bytes = (int)$bytes;
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}

function fo_ext($name) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return preg_match('/^[a-z0-9]{1,8}$/', $ext) ? $ext : '';
}

function fo_kind($item) {
    if ($item['dir']) {
        return 'folder';
    }
    $map = array(
        'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image',
        'webp' => 'image', 'svg' => 'image', 'bmp' => 'image', 'ico' => 'image',
        'txt' => 'text', 'md' => 'text', 'json' => 'text', 'csv' => 'text',
        'log' => 'text', 'ini' => 'text', 'yml' => 'text', 'yaml' => 'text',
        'css' => 'text', 'js' => 'text', 'html' => 'text', 'xml' => 'text',
        'php' => 'code', 'zip' => 'archive', 'tar' => 'archive', 'gz' => 'archive',
        'rar' => 'archive', '7z' => 'archive', 'pdf' => 'pdf',
        'mp3' => 'audio', 'wav' => 'audio', 'ogg' => 'audio',
        'mp4' => 'video', 'webm' => 'video', 'mkv' => 'video', 'mov' => 'video',
    );
    return isset($map[$item['ext']]) ? $map[$item['ext']] : 'file';
}

function fo_list($dir, $showonly = '') {
    $only = array();
    foreach (explode(',', strtolower((string)$showonly)) as $e) {
        $e = trim($e);
        if ($e !== '') $only[] = ltrim($e, '.');
    }
    $items = array();
    $names = @scandir($dir);
    if ($names === false) {
        return $items;
    }
    foreach ($names as $name) {
        if ($name === '.' || $name === '..') continue;
        if (isset($name[0]) && $name[0] === '.') continue;
        $path = $dir . '/' . $name;
        $isDir = is_dir($path);
        if (!$isDir && !is_file($path)) continue;
        $ext = $isDir ? '' : fo_ext($name);
        if (!$isDir && $only && !in_array($ext, $only, true)) continue;
        $item = array(
            'name' => $name, 'path' => $path, 'dir' => $isDir, 'ext' => $ext,
            'size' => $isDir ? 0 : (int)@filesize($path),
            'mtime' => (int)@filemtime($path),
        );
        $item['kind'] = fo_kind($item);
        $items[] = $item;
    }
    usort($items, function ($a, $b) {
        if ($a['dir'] !== $b['dir']) return $a['dir'] ? -1 : 1;
        return strnatcasecmp($a['name'], $b['name']);
    });
    return $items;
}

function fo_unique($dir, $name) {
    $target = $dir . '/' . $name;
    if (!file_exists($target)) return $target;
    $pi = pathinfo($name);
    $base = $pi['filename'];
    $ext = isset($pi['extension']) && $pi['extension'] !== '' ? '.' . $pi['extension'] : '';
    $n = 1;
    do {
        $target = $dir . '/' . $base . ' (' . $n . ')' . $ext;
        $n++;
    } while (file_exists($target) && $n < 1000);
    return $target;
}

function fo_copy_rec($src, $dst) {
    if (is_file($src)) {
        return @copy($src, $dst);
    }
    if (!@mkdir($dst, 0775, true)) return false;
    $ok = true;
    foreach (scandir($src) as $n) {
        if ($n === '.' || $n === '..') continue;
        $s = $src . '/' . $n;
        $d = $dst . '/' . $n;
        $ok = is_dir($s) ? fo_copy_rec($s, $d) : @copy($s, $d);
        if (!$ok) return false;
    }
    return $ok;
}

function fo_delete_rec($path) {
    if (is_file($path)) return @unlink($path);
    if (!is_dir($path)) return false;
    $ok = true;
    foreach (scandir($path) as $n) {
        if ($n === '.' || $n === '..') continue;
        $ok = fo_delete_rec($path . '/' . $n) && $ok;
    }
    return @rmdir($path) && $ok;
}

function fo_dir_stats($path) {
    $size = 0; $count = 0;
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $f) {
            if ($f->isFile()) { $size += (int)$f->getSize(); $count++; }
        }
    } catch (Exception $e) { /* ignore unreadable */ }
    return array($size, $count);
}

function fo_svg($name) {
    $paths = array(
        'chevron-left' => '<path d="M15 18l-6-6 6-6"/>',
        'chevron-right' => '<path d="M9 18l6-6-6-6"/>',
        'home' => '<path d="M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'desktop' => '<rect x="3" y="4" width="18" height="12" rx="1"/><path d="M8 20h8M12 16v4"/>',
        'documents' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
        'downloads' => '<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'upload' => '<path d="M12 21V9M7 14l5-5 5 5M4 3h16"/>',
        'folder' => '<path d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
    );
    if (!isset($paths[$name])) return '';
    return '<svg class="fo-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

function fo_thumb($item) {
    if ($item['dir']) {
        return '<div class="fo-thumb is-folder"></div>';
    }
    if ($item['kind'] === 'image') {
        $url = 'system/apps/Explorer/main.php?action=raw&path=' . rawurlencode($item['path']);
        return '<div class="fo-thumb is-image" style="background-image:url(\'' . h($url) . '\')"></div>';
    }
    $label = $item['ext'] !== '' ? $item['ext'] : '•';
    return '<div class="fo-thumb is-file" data-ext="' . h($label) . '"></div>';
}

function fo_kind_label($item, $L) {
    if ($item['dir']) return $L['folder'];
    $labels = array(
        'image' => 'Изображение', 'text' => 'Текст', 'code' => 'Код',
        'archive' => 'Архив', 'pdf' => 'PDF', 'audio' => 'Аудио',
        'video' => 'Видео', 'file' => $L['file'],
    );
    return isset($labels[$item['kind']]) ? $labels[$item['kind']] : $L['file'];
}

function fo_favs_load(string $home): array
{
    $file = $home . '/settings/favorites.json';
    if (!is_file($file)) {
        return array();
    }
    $j = json_decode((string)@file_get_contents($file), true);
    if (!is_array($j)) {
        return array();
    }
    $out = array();
    foreach ($j as $f) {
        if (!is_array($f)) {
            continue;
        }
        $rel = trim((string)($f['path'] ?? ''), '/');
        if ($rel === '') {
            continue;
        }
        $out[] = array('name' => (string)($f['name'] ?? basename($rel)), 'path' => $rel);
    }
    return $out;
}

function fo_favs_save(string $home, array $list): void
{
    $file = $home . '/settings/favorites.json';
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @file_put_contents($file, json_encode(array_values($list), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    @chmod($file, 0666);
}

function fo_fav_resolve(string $rel, string $home, string $doc): ?string
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if ($rel === '') {
        return null;
    }
    foreach (array($doc . '/' . $rel, $home . '/' . $rel) as $abs) {
        if (is_dir($abs)) {
            return $abs;
        }
    }
    return null;
}

function fo_favs_public(string $home, array $list): array
{
    $doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $out = array();
    foreach ($list as $f) {
        $rel = (string)($f['path'] ?? '');
        $abs = fo_fav_resolve($rel, $home, $doc);
        if ($abs === null) {
            continue;
        }
        $out[] = array('name' => (string)($f['name'] ?? basename($abs)), 'path' => $abs);
    }
    return $out;
}

function fo_render_main($dir, $home, $L, $showonly = '', $view = 'icons', $mobile = 'false', $selectorMode = '', $callback = '', $allowSystem = false) {
    $doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
    $systemRoot = $doc . '/system';
    $inHome = fo_in_home($dir, $home);
    $base = $inHome ? $home : $systemRoot;
    $items = fo_list($dir, $showonly);
    $rel = fo_rel($dir, $base);
    $parent = ($dir === $base) ? $dir : dirname($dir);
    $name = ($dir === $home) ? $L['home'] : (($dir === $systemRoot) ? 'Система' : basename($dir));
    $isTrash = $inHome && basename($dir) === 'trash' && dirname($dir) === $home;
    $readonly = !$inHome && !$allowSystem;

    $crumbs = array(array('name' => $inHome ? $L['home'] : 'Система', 'path' => $base));
    if ($rel !== '') {
        $acc = $base;
        foreach (explode('/', $rel) as $seg) {
            $acc .= '/' . $seg;
            $crumbs[] = array('name' => $seg, 'path' => $acc);
        }
    }

    $free = @disk_free_space($home);
    ob_start();
    ?>
<div class="fo-toolbar">
  <div class="fo-navbtns">
    <button type="button" class="fo-tb" data-cmd="back" title="<?php echo h($L['back']); ?>"><?php echo fo_svg('chevron-left'); ?></button>
    <button type="button" class="fo-tb" data-cmd="forward" title="<?php echo h($L['forward']); ?>"><?php echo fo_svg('chevron-right'); ?></button>
  </div>
  <div class="fo-title" title="<?php echo h($name); ?>"><?php echo h($name); ?></div>
  <?php if ($readonly): ?><span class="fo-ro">Только чтение</span><?php endif; ?>
  <div class="fo-tools">
    <div class="fo-seg" role="group">
      <button type="button" class="fo-segbtn<?php echo $view === 'icons' ? ' is-on' : ''; ?>" data-view="icons" title="<?php echo h($L['icons']); ?>"><?php echo fo_svg('grid'); ?></button>
      <button type="button" class="fo-segbtn<?php echo $view === 'list' ? ' is-on' : ''; ?>" data-view="list" title="<?php echo h($L['list']); ?>"><?php echo fo_svg('list'); ?></button>
    </div>
    <label class="fo-search"><?php echo fo_svg('search'); ?><input type="search" class="fo-search-input" placeholder="<?php echo h($L['search']); ?>" value=""></label>
    <button type="button" class="fo-tb" data-cmd="mkdir" title="<?php echo h($L['new_folder']); ?>"<?php echo $readonly ? ' disabled' : ''; ?>><?php echo fo_svg('plus'); ?></button>
    <button type="button" class="fo-tb" data-cmd="upload" title="<?php echo h($L['upload']); ?>"<?php echo $readonly ? ' disabled' : ''; ?>><?php echo fo_svg('upload'); ?></button>
  </div>
</div>

<div class="fo-crumbs">
  <?php foreach ($crumbs as $i => $c): ?>
    <?php if ($i): ?><span class="fo-crumb-sep">›</span><?php endif; ?>
    <button type="button" class="fo-crumb" data-nav="<?php echo h($c['path']); ?>"><?php echo h($c['name']); ?></button>
  <?php endforeach; ?>
  <?php if ($isTrash): ?>
    <span class="fo-trashbadge"><?php echo h($L['trash']); ?></span>
  <?php endif; ?>
</div>

<div class="fo-content is-<?php echo $view === 'list' ? 'list' : 'icons'; ?>" data-dir="<?php echo h($dir); ?>" data-parent="<?php echo h($parent); ?>">
  <div class="fo-listhead" aria-hidden="true">
    <span><?php echo h($L['name']); ?></span><span><?php echo h($L['date']); ?></span><span><?php echo h($L['size']); ?></span><span><?php echo h($L['kind']); ?></span>
  </div>
  <div class="fo-items">
    <?php if (!$items): ?>
      <div class="fo-empty"><?php echo h($L['empty']); ?></div>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="fo-item" tabindex="0"
             data-fo-out="path" data-fo-value="<?php echo h($it['path']); ?>"
             data-path="<?php echo h($it['path']); ?>"
             data-name="<?php echo h($it['name']); ?>"
             data-isdir="<?php echo $it['dir'] ? '1' : '0'; ?>"
             data-ext="<?php echo h($it['ext']); ?>"
             data-kind="<?php echo h($it['kind']); ?>">
          <div class="fo-cell-main">
            <?php echo fo_thumb($it); ?>
            <div class="fo-name"><?php echo h($it['name']); ?></div>
          </div>
          <div class="fo-date"><?php echo $it['mtime'] ? date('d.m.y H:i', $it['mtime']) : '—'; ?></div>
          <div class="fo-size"><?php echo $it['dir'] ? '—' : h(fo_size($it['size'])); ?></div>
          <div class="fo-kind"><?php echo h(fo_kind_label($it, $L)); ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="fo-status">
  <span class="fo-status-count"><?php echo count($items); ?> <?php echo h($L['items']); ?></span>
  <?php if ($isTrash): ?>
    <button type="button" class="fo-linkbtn" data-cmd="emptytrash"><?php echo h($L['empty_trash']); ?></button>
  <?php endif; ?>
  <?php if ($selectorMode === 'selector' && $callback !== ''): ?>
    <button type="button" class="fo-linkbtn" data-cmd="selectfile"><?php echo h($L['select_file']); ?></button>
  <?php endif; ?>
  <?php if ($mobile === 'true'): ?>
    <button type="button" class="fo-linkbtn" data-cmd="selectmode"><?php echo h($L['select']); ?></button>
  <?php endif; ?>
  <span class="fo-status-spacer"></span>
  <?php if ($free !== false): ?><span class="fo-status-free"><?php echo h(fo_size($free)); ?> <?php echo h($L['free']); ?></span><?php endif; ?>
</div>
    <?php
    return ob_get_clean();
}

/* ---------- auth gate ---------------------------------------------------- */
if (!$isAuth) {
    if (!empty($_POST) || isset($_GET['partial'])) {
        http_response_code(401);
        fo_json(array('ok' => false, 'error' => $L['session']));
    }
    echo '<div class="fo-app"><div class="fo-expired">' . h($L['session']) . '</div></div>';
    exit;
}

$home = realpath($DOC . '/system/users/' . $login);
if ($home === false || !is_dir($home)) {
    http_response_code(404);
    exit('Home not found');
}
$home = str_replace('\\', '/', $home);
$allowSystem = isset($_SESSION['superuser']) && $_SESSION['superuser'] === $login;

/* Корзина должна существовать всегда: на неё ссылается ярлык рабочего стола. */
$trashDir = $home . '/trash';
if (!is_dir($trashDir)) {
    @mkdir($trashDir, 0777, true);
}

if (empty($_SESSION['fo_explorer_csrf'])) {
    $_SESSION['fo_explorer_csrf'] = bin2hex(random_bytes(16));
}
$TOKEN = $_SESSION['fo_explorer_csrf'];

$showonly = isset($_GET['showonly']) ? (string)$_GET['showonly'] : (isset($_POST['showonly']) ? (string)$_POST['showonly'] : '');
$view = (isset($_GET['view']) && $_GET['view'] === 'list') ? 'list' : 'icons';

/* Mercury SelectFile(): the callback data arrives as POST (see AppGUI::CallbackSelectFile) */
if ($showonly === '' && isset($_POST['showonly'])) { $showonly = (string)$_POST['showonly']; }
if ($explorerMode === '' && isset($_POST['explorermode'])) { $explorerMode = preg_replace('/[^A-Za-z0-9_]/', '', (string)$_POST['explorermode']); }
if ($callback === '' && isset($_POST['callback'])) { $callback = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$_POST['callback']); }
if ($defaultDirectory === '' && isset($_POST['defaultdirectory'])) { $defaultDirectory = (string)$_POST['defaultdirectory']; }

/* ---------- streaming ---------------------------------------------------- */
$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
if ($action === 'raw' || $action === 'text' || $action === 'info') {
    $path = fo_resolve($home, isset($_GET['path']) ? $_GET['path'] : '', $allowSystem);
    if ($path === null) {
        http_response_code(404);
        exit('Not found');
    }
    if (!fo_in_home($path, $home) && !$allowSystem && $action !== 'info') {
        $safeExt = array('png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'css', 'js', 'json', 'txt', 'md', 'ico');
        if (!in_array(fo_ext($path), $safeExt, true)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    if ($action === 'info') {
        clearstatcache(true, $path);
        $isDir = is_dir($path);
        $size = $isDir ? 0 : (int)@filesize($path);
        $count = 0;
        if ($isDir) {
            list($size, $count) = fo_dir_stats($path);
        }
        $item = array('dir' => $isDir, 'ext' => $isDir ? '' : fo_ext(basename($path)));

        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $rel = (strpos($path, $docRoot . '/') === 0) ? substr($path, strlen($docRoot) + 1) : '';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $urlPath = ($rel !== '') ? implode('/', array_map('rawurlencode', explode('/', $rel))) : '';
        $url = ($rel !== '') ? $scheme . '://' . $host . '/' . $urlPath : $path;

        fo_json(array(
            'ok' => true,
            'name' => basename($path),
            'path' => $path,
            'url' => $url,
            'dir' => $isDir,
            'size' => $size,
            'size_text' => fo_size($size),
            'count' => $count,
            'mtime' => date('d.m.Y H:i:s', (int)@filemtime($path)),
            'ctime' => date('d.m.Y H:i:s', (int)@filectime($path)),
            'perms' => substr(sprintf('%o', @fileperms($path)), -4),
            'kind' => fo_kind_label($item, $L),
        ));
    }

    if (!is_file($path)) {
        http_response_code(404);
        exit('Not found');
    }
    $ext = fo_ext($path);
    $name = basename($path);

    if ($action === 'text') {
        $textExt = array('txt', 'md', 'json', 'csv', 'log', 'ini', 'yml', 'yaml',
                         'php', 'phtml',
                         'css', 'js', 'html', 'xml', 'conf', 'cfg');
        if (!in_array($ext, $textExt, true)) { http_response_code(415); exit('Not previewable'); }
        if (filesize($path) > 262144) { http_response_code(413); exit('File is too large'); }
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    $mimes = array(
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
        'bmp' => 'image/bmp', 'ico' => 'image/x-icon', 'pdf' => 'application/pdf',
        'mp3' => 'audio/mpeg', 'mp4' => 'video/mp4', 'webm' => 'video/webm',
    );
    $mime = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, no-store');
    if (isset($_GET['download'])) {
        header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"');
    }
    readfile($path);
    exit;
}

/* ---------- state-changing actions -------------------------------------- */
$postAction = isset($_POST['action']) ? (string)$_POST['action'] : '';
if ($postAction !== '') {
    if (!hash_equals($TOKEN, isset($_POST['token']) ? (string)$_POST['token'] : '')) {
        fo_json(array('ok' => false, 'error' => $L['err_token']));
    }
    $dir = fo_resolve($home, isset($_POST['dir']) ? $_POST['dir'] : '', $allowSystem);
    if ($dir === null || !is_dir($dir)) {
        fo_json(array('ok' => false, 'error' => $L['err_path']));
    }
    if (!fo_in_home($dir, $home) && !$allowSystem) {
        fo_json(array('ok' => false, 'error' => 'Только чтение'));
    }

    $paths = array();
    if (isset($_POST['paths']) && is_array($_POST['paths'])) {
        foreach ($_POST['paths'] as $p) {
            $rp = fo_resolve($home, $p, $allowSystem);
            if ($rp !== null && $rp !== $home && (fo_in_home($rp, $home) || $allowSystem)) {
                $paths[] = $rp;
            }
        }
    }

    if ($postAction === 'favadd' || $postAction === 'favdel') {
        $target = fo_resolve($home, isset($_POST['path']) ? $_POST['path'] : '', $allowSystem);
        if ($target === null || !is_dir($target)) {
            fo_json(array('ok' => false, 'error' => $L['err_path']));
        }
        if (!fo_in_home($target, $home) && !$allowSystem) {
            fo_json(array('ok' => false, 'error' => 'Только чтение'));
        }
        /* храним путь от корня сайта — так работают и системные папки */
        $rel = ltrim(str_replace($DOC, '', $target), '/');
        $list = fo_favs_load($home);
        if ($postAction === 'favadd') {
            $exists = false;
            foreach ($list as $f) {
                if ((string)$f['path'] === $rel) { $exists = true; break; }
            }
            if (!$exists) {
                $list[] = array('name' => basename($rel), 'path' => $rel);
            }
        } else {
            $list = array_values(array_filter($list, function ($f) use ($target, $home, $DOC) {
                $abs = fo_fav_resolve((string)$f['path'], $home, $DOC);
                return $abs === null || $abs !== $target;
            }));
        }
        fo_favs_save($home, $list);
        fo_json(array('ok' => true, 'favs' => fo_favs_public($home, $list)));
    }

    if ($postAction === 'mkdir' || $postAction === 'newfile') {
        $name = fo_clean_name(isset($_POST['name']) ? $_POST['name'] : '');
        if ($name === '') fo_json(array('ok' => false, 'error' => $L['err_name']));
        $target = fo_unique($dir, $name);
        if ($postAction === 'mkdir') {
            $ok = @mkdir($target, 0775);
        } else {
            $ok = @file_put_contents($target, '') !== false;
            if ($ok) @chmod($target, 0664);
        }
        if (!$ok) fo_json(array('ok' => false, 'error' => $L['err_perm']));
        fo_json(array('ok' => true, 'name' => basename($target)));
    }

    if ($postAction === 'rename') {
        $src = isset($paths[0]) ? $paths[0] : null;
        $newName = fo_clean_name(isset($_POST['name']) ? $_POST['name'] : '');
        if ($src === null || $newName === '') fo_json(array('ok' => false, 'error' => $L['err_name']));
        $dst = fo_unique(dirname($src), $newName);
        if (!@rename($src, $dst)) fo_json(array('ok' => false, 'error' => $L['err_perm']));
        fo_json(array('ok' => true));
    }

    if ($postAction === 'delete' || $postAction === 'deleteperm') {
        if (!$paths) fo_json(array('ok' => false, 'error' => $L['err_path']));
        $trash = $home . '/trash';
        if ($postAction === 'delete' && !is_dir($trash)) {
            @mkdir($trash, 0775, true);
        }
        $done = 0;
        foreach ($paths as $src) {
            if ($postAction === 'delete') {
                if ($src === $trash) continue;
                $dst = fo_unique($trash, basename($src));
                if (@rename($src, $dst)) $done++;
            } else {
                if (fo_delete_rec($src)) $done++;
            }
        }
        if (!$done) fo_json(array('ok' => false, 'error' => $L['err_perm']));
        fo_json(array('ok' => true, 'count' => $done));
    }

    if ($postAction === 'emptytrash') {
        $trash = $home . '/trash';
        if (!is_dir($trash)) fo_json(array('ok' => true, 'count' => 0));
        $done = 0;
        foreach (scandir($trash) as $n) {
            if ($n === '.' || $n === '..') continue;
            if (fo_delete_rec($trash . '/' . $n)) $done++;
        }
        fo_json(array('ok' => true, 'count' => $done));
    }

    if ($postAction === 'paste') {
        if (!$paths) fo_json(array('ok' => false, 'error' => $L['clipboard_empty']));
        $mode = (isset($_POST['mode']) && $_POST['mode'] === 'cut') ? 'cut' : 'copy';
        $done = 0; $failed = 0;
        foreach ($paths as $src) {
            if ($mode === 'cut' && $src === $dir) { continue; }
            if (is_dir($src) && ($dir === $src || strpos($dir . '/', $src . '/') === 0)) {
                $failed++; continue;
            }
            $dst = fo_unique($dir, basename($src));
            if ($mode === 'cut') {
                $ok = @rename($src, $dst);
                if (!$ok) {
                    $ok = fo_copy_rec($src, $dst) && fo_delete_rec($src);
                }
            } else {
                $ok = fo_copy_rec($src, $dst);
            }
            $ok ? $done++ : $failed++;
            if ($done >= 200) break;
        }
        if (!$done) fo_json(array('ok' => false, 'error' => $L['err_perm']));
        fo_json(array('ok' => true, 'count' => $done, 'failed' => $failed));
    }

    if ($postAction === 'zip') {
        if (!$paths) fo_json(array('ok' => false, 'error' => $L['err_path']));
        $baseName = (count($paths) === 1)
            ? preg_replace('/\.[^.\/]+$/', '', basename($paths[0])) . '.zip'
            : 'Архив.zip';
        $zipPath = fo_unique($dir, $baseName);
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            fo_json(array('ok' => false, 'error' => $L['err_perm']));
        }
        $addDir = function ($zip, $src, $prefix) use (&$addDir) {
            foreach (scandir($src) as $n) {
                if ($n === '.' || $n === '..') continue;
                $p = $src . '/' . $n;
                $local = $prefix . '/' . $n;
                if (is_dir($p)) {
                    $zip->addEmptyDir($local);
                    $addDir($zip, $p, $local);
                } else {
                    $zip->addFile($p, $local);
                }
            }
        };
        foreach ($paths as $src) {
            if (is_file($src)) {
                $zip->addFile($src, basename($src));
            } else {
                $zip->addEmptyDir(basename($src));
                $addDir($zip, $src, basename($src));
            }
        }
        $zip->close();
        @chmod($zipPath, 0664);
        fo_json(array('ok' => true, 'name' => basename($zipPath)));
    }

    if ($postAction === 'unzip') {
        $src = isset($paths[0]) ? $paths[0] : null;
        if ($src === null || !is_file($src) || fo_ext($src) !== 'zip') {
            fo_json(array('ok' => false, 'error' => $L['err_path']));
        }
        $zip = new ZipArchive();
        if ($zip->open($src) !== true) fo_json(array('ok' => false, 'error' => $L['err_perm']));
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $n = $zip->getNameIndex($i);
            if ($n === false || $n === '' || $n[0] === '/' || strpos($n, '..') !== false) {
                $zip->close();
                fo_json(array('ok' => false, 'error' => 'Небезопасный архив'));
            }
        }
        $target = fo_unique(dirname($src), preg_replace('/\.zip$/i', '', basename($src)));
        if (!@mkdir($target, 0775)) { $zip->close(); fo_json(array('ok' => false, 'error' => $L['err_perm'])); }
        $zip->extractTo($target);
        $zip->close();
        fo_json(array('ok' => true, 'name' => basename($target)));
    }

    if ($postAction === 'link') {
        $src = isset($paths[0]) ? $paths[0] : null;
        if ($src === null) fo_json(array('ok' => false, 'error' => $L['err_path']));
        $abs = fo_resolve($home, $src, $allowSystem);
        if ($abs === null) fo_json(array('ok' => false, 'error' => $L['err_path']));

        $desktop = $home . '/desktop';
        if (!is_dir($desktop)) @mkdir($desktop, 0775, true);

        $rel = ltrim(str_replace('\\', '/', (string)$abs), '/');
        $DOCn = rtrim(str_replace('\\', '/', $DOC), '/');
        if (strpos($rel, ltrim($DOCn, '/') . '/') === 0) {
            $rel = substr($rel, strlen(ltrim($DOCn, '/')) + 1);
        }
        $displayName = basename($rel);

        // defaults: a plain file opens in the Explorer's viewer
        $destination = 'system/apps/Explorer/main.php';
        $key = 'open';
        $param = $rel;
        $icon = '';
        $kind = 'file';
        $linkName = $displayName;

        if (preg_match('#^system/apps/([A-Za-z0-9_.\-]+)/.+\.php$#', $rel, $m)) {
            // shortcut to an application: launches it
            $app = $m[1];
            $destination = 'system/apps/' . $app . '/main.php';
            $key = '';
            $param = '';
            $linkName = $app;
            $kind = 'app';
            if (is_file($DOC . '/system/apps/' . $app . '/app.png')) {
                $icon = 'system/apps/' . $app . '/app.png';
            }
        } elseif (is_dir($abs)) {
            $destination = 'system/apps/Explorer/main.php';
            $key = 'dir';
            $param = $rel;
            $kind = 'dir';
            $icon = 'system/core/design/images/folder.png';
        } else {
            $ext = strtolower((string)pathinfo($rel, PATHINFO_EXTENSION));
            $cfg = @parse_ini_file($DOC . '/system/core/extconfiguration.foc');
            $assoc = (is_array($cfg) && $ext !== '' && !empty($cfg[$ext])) ? ltrim((string)$cfg[$ext], '/') : '';
            if ($assoc !== '' && preg_match('#^system/apps/[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+\.php$#', $assoc) && is_file($DOC . '/' . $assoc)) {
                $destination = $assoc;
                $key = (is_array($cfg) && !empty($cfg[$ext . '_key'])) ? (string)$cfg[$ext . '_key'] : 'open';
                $param = '/' . $rel;
                $appDir = dirname($assoc);
                if (is_file($DOC . '/' . $appDir . '/app.png')) {
                    $icon = $appDir . '/app.png';
                }
            } else {
                // no association: open the folder that contains the file
                $destination = 'system/apps/Explorer/main.php';
                $key = 'dir';
                $param = dirname($rel) === '.' ? '' : dirname($rel);
            }
        }

        $linkFile = $desktop . '/' . $displayName . '_' . uniqid() . '.link';
        $content = "[link]\n"
            . 'destination=' . $destination . "\n"
            . "file=main\n"
            . 'key=' . $key . "\n"
            . 'param=' . $param . "\n"
            . 'name=' . $linkName . "\n"
            . 'linkname=' . $linkName . "\n"
            . 'icon=' . $icon;
        if (@file_put_contents($linkFile, $content) === false) {
            fo_json(array('ok' => false, 'error' => $L['err_perm']));
        }
        @chmod($linkFile, 0664);
        fo_json(array('ok' => true, 'name' => $linkName, 'kind' => $kind, 'destination' => $destination));
    }

    if ($postAction === 'upload') {
        if (empty($_FILES['files']) || !isset($_FILES['files']['name'])) {
            fo_json(array('ok' => false, 'error' => 'Нет файлов'));
        }
        $names = (array)$_FILES['files']['name'];
        $tmps  = (array)$_FILES['files']['tmp_name'];
        $errs  = (array)$_FILES['files']['error'];
        $uploaded = 0; $failed = array();
        foreach ($names as $i => $rawName) {
            if (!isset($tmps[$i], $errs[$i]) || $errs[$i] !== UPLOAD_ERR_OK || !is_uploaded_file($tmps[$i])) {
                $failed[] = $rawName; continue;
            }
            $clean = fo_clean_name($rawName);
            if ($clean === '') { $failed[] = $rawName; continue; }
            $target = fo_unique($dir, $clean);
            if (!@move_uploaded_file($tmps[$i], $target)) { $failed[] = $rawName; continue; }
            @chmod($target, 0664);
            $uploaded++;
        }
        if ($uploaded === 0) fo_json(array('ok' => false, 'error' => $L['err_perm'], 'failed' => $failed));
        fo_json(array('ok' => true, 'uploaded' => $uploaded, 'failed' => $failed));
    }

    fo_json(array('ok' => false, 'error' => 'Unknown action'));
}

/* ---------- page / partial --------------------------------------------- */
$requestedDir = (isset($_GET['defaultloader']) && $_GET['defaultloader'] !== '')
    ? $_GET['defaultloader']
    : (isset($_GET['dir']) ? $_GET['dir'] : '');
$requestedOpen = (isset($_GET['open']) && $_GET['open'] !== '') ? (string)$_GET['open'] : '';
if ($requestedOpen !== '' && $requestedDir === '') {
    $requestedDir = dirname($requestedOpen) === '.' ? '' : dirname($requestedOpen);
}
if ($defaultDirectory !== '') {
    $requestedDir = $defaultDirectory;
}
$dir = fo_resolve($home, $requestedDir, $allowSystem);
if ($dir === null) {
    $dir = $home;
} elseif (!is_dir($dir)) {
    $dir = dirname($dir);
}

$apps = array();
foreach (glob($DOC . '/system/apps/*/main.php') as $f) {
    $slug = basename(dirname($f));
    $apps[] = array('name' => str_replace('_', ' ', $slug), 'slug' => $slug, 'path' => 'system/apps/' . $slug . '/main.php');
}

$favsPublic = fo_favs_public($home, fo_favs_load($home));

/* default handlers registered by applications (extconfiguration.foc) */
$assocMap = array();
$assocCfg = @parse_ini_file($DOC . '/system/core/extconfiguration.foc');
if (is_array($assocCfg)) {
    foreach ($assocCfg as $k => $v) {
        $k = (string)$k;
        if ($k === '' || substr($k, -4) === '_key') {
            continue;
        }
        $app = ltrim((string)$v, '/');
        if ($app === '' || preg_match('#^system/apps/Explorer/#i', $app)) {
            continue;
        }
        $assocMap[strtolower($k)] = array(
            'app' => $app,
            'key' => (string)($assocCfg[$k . '_key'] ?? 'defaultloader'),
        );
    }
}

if (isset($_GET['partial'])) {
    header('Content-Type: text/html; charset=utf-8');
    echo fo_render_main($dir, $home, $L, $showonly, $view, $mobile, $explorerMode, $callback, $allowSystem);
    exit;
}

require_once $DOC . '/system/core/library/Mercury/AppContainer.php';
$AppContainer = new AppContainer;
$AppContainer->AppNameInfo = 'Explorer';
$AppContainer->SecondNameInfo = 'Проводник';
$AppContainer->VersionInfo = '2.1.0';
$AppContainer->AuthorInfo = 'Forest Media';
$AppContainer->LibraryArray = array();
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '488px';
$AppContainer->width = '820px';
$AppContainer->backgroundColor = '#ffffff';
$AppContainer->fontColor = '#1d1d1f';
$AppContainer->customStyle = 'padding-top:0;max-width:100%;overflow:hidden;';
$AppContainer->isMobile = $mobile;
$AppContainer->StartContainer();

$css = @file_get_contents(__DIR__ . '/assets/explorer2.css');
$js  = @file_get_contents(__DIR__ . '/assets/explorer2.js');
$qrjs = @file_get_contents(__DIR__ . '/assets/qrcode.js');
$rootId = 'fo-app-' . $AppID;
?>
<style><?php echo $css; ?></style>
<div id="<?php echo h($rootId); ?>" class="fo-app"
     data-endpoint="<?php echo h($ENDPOINT); ?>"
     data-token="<?php echo h($TOKEN); ?>"
     data-appname="<?php echo h($AppName); ?>"
     data-appid="<?php echo h($AppID); ?>"
     data-mobile="<?php echo h($mobile); ?>"
     data-home="<?php echo h($home); ?>"
     data-docroot="<?php echo h($DOC); ?>"
     data-apps="<?php echo h(json_encode($apps)); ?>"
     data-assoc="<?php echo h(json_encode($assocMap)); ?>"
     data-favs="<?php echo h(json_encode($favsPublic)); ?>"
     data-mode="<?php echo h($explorerMode); ?>"
     data-open="<?php echo h($requestedOpen); ?>"
     data-callback="<?php echo h($callback); ?>"
     data-showonly="<?php echo h($showonly); ?>">
  <div class="fo-mnav" role="tablist">
    <button type="button" class="fo-mnav-item" data-nav="<?php echo h($home); ?>"><?php echo fo_svg('home'); ?><span><?php echo h($L['home']); ?></span></button>
    <?php foreach (array('desktop' => 'desktop', 'documents' => 'documents', 'downloads' => 'downloads') as $sub => $icon): ?>
      <?php if (is_dir($home . '/' . $sub)): ?>
        <button type="button" class="fo-mnav-item" data-nav="<?php echo h($home . '/' . $sub); ?>"><?php echo fo_svg($icon); ?><span><?php echo h($L[$sub]); ?></span></button>
      <?php endif; ?>
    <?php endforeach; ?>
    <button type="button" class="fo-mnav-item" data-nav="<?php echo h($home . '/trash'); ?>"><?php echo fo_svg('trash'); ?><span><?php echo h($L['trash']); ?></span></button>
    <?php if ($allowSystem): ?>
      <button type="button" class="fo-mnav-item" data-nav="<?php echo h($DOC . '/system'); ?>"><?php echo fo_svg('folder'); ?><span>Система</span></button>
    <?php endif; ?>
  </div>
  <div class="fo-window">
    <aside class="fo-sidebar">
      <div class="fo-side-title"><?php echo h($L['favorites']); ?></div>
      <button type="button" class="fo-side-item" data-nav="<?php echo h($home); ?>"><?php echo fo_svg('home'); ?><span><?php echo h($L['home']); ?></span></button>
      <?php foreach (array('desktop' => 'desktop', 'documents' => 'documents', 'downloads' => 'downloads') as $sub => $icon): ?>
        <?php if (is_dir($home . '/' . $sub)): ?>
          <button type="button" class="fo-side-item" data-nav="<?php echo h($home . '/' . $sub); ?>"><?php echo fo_svg($icon); ?><span><?php echo h($L[$sub]); ?></span></button>
        <?php endif; ?>
      <?php endforeach; ?>
      <div class="fo-side-title">Мои папки</div>
      <div class="fo-favlist"></div>
      <?php if ($allowSystem): ?>
      <div class="fo-side-title">Система</div>
      <button type="button" class="fo-side-item" data-nav="<?php echo h($DOC . '/system'); ?>"><?php echo fo_svg('folder'); ?><span>Система</span></button>
      <?php endif; ?>
      <div class="fo-side-title"><?php echo h($L['trash']); ?></div>
      <button type="button" class="fo-side-item" data-nav="<?php echo h($home . '/trash'); ?>"><?php echo fo_svg('trash'); ?><span><?php echo h($L['trash']); ?></span></button>
    </aside>
    <section class="fo-main">
      <?php echo fo_render_main($dir, $home, $L, $showonly, $view, $mobile, $explorerMode, $callback, $allowSystem); ?>
    </section>
  </div>
  <div class="fo-modal" hidden></div>
  <div class="fo-menu" hidden></div>
  <div class="fo-toast" hidden></div>
  <input type="file" class="fo-file" multiple hidden>
</div>
<script><?php echo $qrjs; ?></script>
<script><?php echo $js; ?></script>
<script>if (window.ForestExplorer) { ForestExplorer.init(document.getElementById(<?php echo json_encode($rootId); ?>)); }</script>
<?php
$AppContainer->EndContainer();
