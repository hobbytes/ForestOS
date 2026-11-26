<?php
/* ==========================================================================
   Forest OS — Rich Text Editor 2.0 (RTE2)
   --------------------------------------------------------------------------
   A clean contenteditable editor with a real toolbar:
     undo/redo, bold/italic/underline/strike, h1-h3, lists, quote, code,
     link, image by URL, alignment, clear formatting.
   Files: opens $_GET['defaultloader'] / $_GET['file'] (and the legacy
   'rteloader'), saves through save.php into the same file, or "Save as"
   into system/users/<login>/documents/.
   Drafts survive a reload / another device through window.FO_APP_STATE.
   All visible text comes from app.lang (ru_* / en_* keys).
   ========================================================================== */

ini_set('display_errors', 'Off');

if (!isset($_SESSION)) {
    session_start();
}

$DOC = rtrim(str_replace('\\', '/', (string)$_SERVER['DOCUMENT_ROOT']), '/');
$login = isset($_SESSION['loginuser'])
    ? preg_replace('/[^A-Za-z0-9_.\-]/', '', (string)$_SESSION['loginuser'])
    : '';

$AppName = (isset($_GET['appname']) && $_GET['appname'] !== '')
    ? preg_replace('/[^A-Za-z0-9_]/', '', (string)$_GET['appname'])
    : 'RTE2';
if ($AppName === '') { $AppName = 'RTE2'; }

$AppID = (isset($_GET['appid']) && $_GET['appid'] !== '')
    ? preg_replace('/[^A-Za-z0-9_]/', '', (string)$_GET['appid'])
    : '0';
if ($AppID === '') { $AppID = '0'; }

/* destination belongs to the launcher (Explorer, Apps House, ...) — accept
   it without trusting it, and expose it to the app for diagnostics only */
$Destination = isset($_GET['destination']) ? (string)$_GET['destination'] : '';
$Destination = str_replace('\\', '/', $Destination);
$Destination = preg_replace('#[^A-Za-z0-9_./\-]#', '', $Destination);

$mobile = isset($_GET['mobile']) ? (string)$_GET['mobile'] : 'false';

/* ---------------- localization ---------------- */

function rte2_dictionary(string $file, string $locale): array
{
    $out = array();
    $ini = @parse_ini_file($file, true, INI_SCANNER_RAW);
    if (!is_array($ini) || empty($ini['RTE2']) || !is_array($ini['RTE2'])) {
        return $out;
    }
    $prefix = $locale . '_';
    $len = strlen($prefix);
    foreach ($ini['RTE2'] as $key => $value) {
        $key = (string)$key;
        if (strpos($key, $prefix) === 0) {
            $out[substr($key, $len)] = (string)$value;
        }
    }
    return $out;
}

$locale = 'ru';
if (isset($_SESSION['locale']) && (string)$_SESSION['locale'] === 'en') {
    $locale = 'en';
}

$L = rte2_dictionary(__DIR__ . '/app.lang', $locale);

function t(string $key): string
{
    global $L;
    return isset($L[$key]) ? $L[$key] : $key;
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* ---------------- csrf for save.php ---------------- */

if (empty($_SESSION['rte2_csrf']) || !is_string($_SESSION['rte2_csrf'])) {
    try {
        $_SESSION['rte2_csrf'] = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $_SESSION['rte2_csrf'] = md5(uniqid('rte2', true));
    }
}
$token = (string)$_SESSION['rte2_csrf'];

/* ---------------- path guards (kept in sync with save.php) ---------------- */

function rte2_inside(string $abs, string $doc): bool
{
    $abs = str_replace('\\', '/', $abs);
    $doc = rtrim(str_replace('\\', '/', $doc), '/');
    return $abs === $doc || strpos($abs, $doc . '/') === 0;
}

function rte2_forbidden(string $abs, string $doc): bool
{
    $abs = str_replace('\\', '/', $abs);
    $doc = rtrim(str_replace('\\', '/', $doc), '/');
    $lower = strtolower(substr($abs, strlen($doc)));
    if (strpos($lower, '/system/core/') === 0 || $lower === '/system/core') {
        return true;
    }
    /* never let the editor rewrite the shell entry points */
    $base = strtolower(basename($abs));
    if ($base === 'os.php' || $base === 'login.php') {
        return true;
    }
    /* and never itself */
    if (strpos($lower, '/system/apps/rte2/') === 0) {
        return true;
    }
    return false;
}

function rte2_candidate(string $raw, string $doc): ?string
{
    $raw = trim(str_replace('\\', '/', $raw));
    if ($raw === '') {
        return null;
    }
    $doc = rtrim(str_replace('\\', '/', $doc), '/');
    if (stripos($raw, $doc . '/') === 0) {
        return $raw;
    }
    if (preg_match('#^[A-Za-z]:/#', $raw)) {
        return $raw;
    }
    if (strpos($raw, '/') === 0) {
        return $doc . $raw;
    }
    return $doc . '/' . $raw;
}

/* ---------------- open the requested file ---------------- */

$requested = '';
foreach (array('defaultloader', 'file', 'rteloader') as $key) {
    if (isset($_GET[$key]) && (string)$_GET[$key] !== '') {
        $requested = (string)$_GET[$key];
        break;
    }
}

$target = '';
$content = '';
$format = 'html';
$loadError = '';

if ($requested !== '') {
    $candidate = rte2_candidate($requested, $DOC);
    $real = ($candidate !== null) ? @realpath($candidate) : false;
    if ($real === false) {
        $loadError = 'not_found';
    } else {
        $real = str_replace('\\', '/', $real);
        if (!rte2_inside($real, $DOC)) {
            $loadError = 'outside';
        } elseif (rte2_forbidden($real, $DOC)) {
            $loadError = 'forbidden';
        } elseif (!is_file($real) || !is_readable($real)) {
            $loadError = 'not_found';
        } else {
            $size = @filesize($real);
            if ($size === false || $size > 4 * 1024 * 1024) {
                $loadError = 'too_large';
            } else {
                $raw = @file_get_contents($real);
                if ($raw === false) {
                    $loadError = 'not_found';
                } else {
                    $content = $raw;
                    $target = ltrim(substr($real, strlen($DOC)), '/');
                    $ext = strtolower((string)pathinfo($real, PATHINFO_EXTENSION));
                    $format = in_array($ext, array('html', 'htm', 'xhtml'), true) ? 'html' : 'text';
                }
            }
        }
    }
}

$documentsRel = 'system/users/' . $login . '/documents';

$appDirUrl = rtrim(str_replace('\\', '/', dirname((string)$_SERVER['SCRIPT_NAME'])), '/');
if ($appDirUrl === '' || $appDirUrl === '.') {
    $appDirUrl = '/system/apps/RTE2';
}

/* ---------------- boot payload for the client ---------------- */

$boot = array(
    'format' => $format,
    'content' => $content,
    'target' => $target,
    'name' => ($target !== '') ? basename($target) : '',
    'login' => $login,
    'documents' => $documentsRel,
    'endpoint' => $appDirUrl . '/save.php',
    'token' => $token,
    'error' => $loadError,
    'requested' => ($requested !== '') ? basename(str_replace('\\', '/', $requested)) : '',
    'launcher' => $Destination,
    'locale' => $locale,
    'i18n' => $L,
);

$bootJson = json_encode(
    $boot,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP |
    JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
);
if (!is_string($bootJson) || $bootJson === '') {
    $bootJson = '{}';
}

$css = @file_get_contents(__DIR__ . '/assets/rte2.css');
$js = @file_get_contents(__DIR__ . '/assets/rte2.js');
if (!is_string($css)) { $css = ''; }
if (!is_string($js)) { $js = ''; }

/* ---------------- inline SVG icons ---------------- */

$icoDoc = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 2.6h4.9L12 5.7v7.7H4z"/><path d="M8.9 2.6v3.1H12"/><path d="M6 8.6h4M6 10.7h4"/></svg>';
$icoNew = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 2.6h4.9L12 5.7v7.7H4z"/><path d="M8 7.6v4.2M5.9 9.7h4.2"/></svg>';
$icoSaveAs = '<svg viewBox="0 0 16 16" aria-hidden="true"><rect x="5.4" y="3" width="7" height="9.4" rx="1.2"/><path d="M3.4 5.4v7.2h6.6"/></svg>';
$icoSave = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3.2 8.5 6 11.3l6.8-6.8"/></svg>';
$icoUndo = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M5.6 4.4 2.6 7.4l3 3"/><path d="M2.6 7.4h7.1a3.4 3.4 0 0 1 3.4 3.4v1.2"/></svg>';
$icoRedo = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M10.4 4.4l3 3-3 3"/><path d="M13.4 7.4H6.3a3.4 3.4 0 0 0-3.4 3.4v1.2"/></svg>';
$icoUl = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M6 4.2h7.4M6 8h7.4M6 11.8h7.4"/><circle class="rte2-fill" cx="2.8" cy="4.2" r="1.05"/><circle class="rte2-fill" cx="2.8" cy="8" r="1.05"/><circle class="rte2-fill" cx="2.8" cy="11.8" r="1.05"/></svg>';
$icoOl = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M6 4.2h7.4M6 8h7.4M6 11.8h7.4"/><text class="rte2-num" x="1" y="5.7">1</text><text class="rte2-num" x="1" y="9.5">2</text><text class="rte2-num" x="1" y="13.3">3</text></svg>';
$icoLink = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M6.6 9.4 9.4 6.6"/><path d="M7.4 4.7 8.9 3.2a2.7 2.7 0 0 1 3.8 3.8l-1.5 1.5"/><path d="M8.6 11.3 7.1 12.8a2.7 2.7 0 0 1-3.8-3.8l1.5-1.5"/></svg>';
$icoUnlink = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8.9 3.2a2.7 2.7 0 0 1 3.8 3.8l-1.5 1.5"/><path d="M7.1 12.8a2.7 2.7 0 0 1-3.8-3.8l1.5-1.5"/><path d="M2.6 2.6l10.8 10.8"/></svg>';
$icoImage = '<svg viewBox="0 0 16 16" aria-hidden="true"><rect x="2.5" y="3.5" width="11" height="9" rx="1.6"/><circle cx="6" cy="6.7" r="1.1"/><path d="M3.4 11.6 6.5 8.6l2.1 1.9 1.9-1.6 1.9 2.7"/></svg>';
$icoLeft = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2.6 4.1h10.8M2.6 8h6.9M2.6 11.9h9.2"/></svg>';
$icoCenter = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2.6 4.1h10.8M4.6 8h6.9M3.4 11.9h9.2"/></svg>';
$icoRight = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2.6 4.1h10.8M6.5 8h6.9M4.2 11.9h9.2"/></svg>';
$icoJustify = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2.6 4.1h10.8M2.6 8h10.8M2.6 11.9h10.8"/></svg>';
$icoClear = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3.4 4.2h7M6.9 4.2v7"/><path d="M10.6 10.4l3 3M13.6 10.4l-3 3"/></svg>';

function rte2_tool(string $cmd, string $title, string $inner): string
{
    return '<button type="button" class="rte2-tb" data-cmd="' . h($cmd) . '"'
        . ' title="' . h($title) . '" aria-label="' . h($title) . '">' . $inner . '</button>';
}

function rte2_block(string $tag, string $title, string $inner): string
{
    return '<button type="button" class="rte2-tb" data-block="' . h($tag) . '"'
        . ' title="' . h($title) . '" aria-label="' . h($title) . '">' . $inner . '</button>';
}

/* ---------------- container ---------------- */

require $DOC . '/system/core/library/Mercury/AppContainer.php';

$AppContainer = new AppContainer;
$AppContainer->AppNameInfo = 'Rich Text Editor 2.0';
$AppContainer->SecondNameInfo = 'Rich Text Editor';
$AppContainer->VersionInfo = '2.0.0';
$AppContainer->AuthorInfo = 'Forest OS';
$AppContainer->LibraryArray = array();
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '100%';
$AppContainer->width = '100%';
$AppContainer->backgroundColor = 'var(--fo-win-body, #fbfbfd)';
$AppContainer->fontColor = 'var(--fo-text, #151a17)';
$AppContainer->customStyle = 'padding-top:0; max-width:100%; max-height:100%; overflow:hidden;';
$AppContainer->isMobile = $mobile;
$AppContainer->StartContainer();
?>
<style><?php echo $css; ?></style>
<div id="rte2<?php echo h($AppID); ?>" class="rte2" data-state="new" data-boot="rte2-boot<?php echo h($AppID); ?>">

  <div class="rte2-banner" data-role="banner" hidden></div>

  <header class="rte2-head">
    <div class="rte2-file">
      <span class="rte2-file-ico" aria-hidden="true"><?php echo $icoDoc; ?></span>
      <span class="rte2-file-meta">
        <span class="rte2-file-name" data-role="file-name"><?php echo h(t('untitled')); ?></span>
        <span class="rte2-file-path" data-role="file-path"></span>
      </span>
    </div>
    <div class="rte2-actions">
      <button type="button" class="rte2-btn" data-act="new" title="<?php echo h(t('new')); ?>" aria-label="<?php echo h(t('new')); ?>">
        <?php echo $icoNew; ?><span class="rte2-btn-label"><?php echo h(t('new')); ?></span>
      </button>
      <button type="button" class="rte2-btn" data-act="saveas" title="<?php echo h(t('save_as')); ?>" aria-label="<?php echo h(t('save_as')); ?>">
        <?php echo $icoSaveAs; ?><span class="rte2-btn-label"><?php echo h(t('save_as')); ?></span>
      </button>
      <button type="button" class="rte2-btn rte2-primary" data-act="save" title="<?php echo h(t('save')); ?>" aria-label="<?php echo h(t('save')); ?>">
        <?php echo $icoSave; ?><span class="rte2-btn-label"><?php echo h(t('save')); ?></span>
      </button>
    </div>
  </header>

  <div class="rte2-toolbar" role="toolbar" aria-label="<?php echo h(t('toolbar_aria')); ?>">
    <div class="rte2-group">
      <?php echo rte2_tool('undo', t('undo'), $icoUndo); ?>
      <?php echo rte2_tool('redo', t('redo'), $icoRedo); ?>
    </div>
    <span class="rte2-sep" aria-hidden="true"></span>
    <div class="rte2-group">
      <?php echo rte2_block('h1', t('h1'), '<span class="rte2-glyph rte2-g-h">H1</span>'); ?>
      <?php echo rte2_block('h2', t('h2'), '<span class="rte2-glyph rte2-g-h">H2</span>'); ?>
      <?php echo rte2_block('h3', t('h3'), '<span class="rte2-glyph rte2-g-h">H3</span>'); ?>
      <?php echo rte2_block('p', t('paragraph'), '<span class="rte2-glyph rte2-g-p">&#182;</span>'); ?>
    </div>
    <span class="rte2-sep" aria-hidden="true"></span>
    <div class="rte2-group">
      <?php echo rte2_tool('bold', t('bold'), '<span class="rte2-glyph rte2-g-b">B</span>'); ?>
      <?php echo rte2_tool('italic', t('italic'), '<span class="rte2-glyph rte2-g-i">I</span>'); ?>
      <?php echo rte2_tool('underline', t('underline'), '<span class="rte2-glyph rte2-g-u">U</span>'); ?>
      <?php echo rte2_tool('strikeThrough', t('strike'), '<span class="rte2-glyph rte2-g-s">S</span>'); ?>
    </div>
    <span class="rte2-sep" aria-hidden="true"></span>
    <div class="rte2-group">
      <?php echo rte2_tool('insertUnorderedList', t('ul'), $icoUl); ?>
      <?php echo rte2_tool('insertOrderedList', t('ol'), $icoOl); ?>
      <?php echo rte2_block('blockquote', t('quote'), '<span class="rte2-glyph rte2-g-quote">&#10077;</span>'); ?>
      <?php echo rte2_block('pre', t('code'), '<span class="rte2-glyph rte2-g-code">&lt;/&gt;</span>'); ?>
    </div>
    <span class="rte2-sep" aria-hidden="true"></span>
    <div class="rte2-group">
      <?php echo rte2_tool('link', t('link'), $icoLink); ?>
      <?php echo rte2_tool('unlink', t('unlink'), $icoUnlink); ?>
      <?php echo rte2_tool('image', t('image'), $icoImage); ?>
    </div>
    <span class="rte2-sep" aria-hidden="true"></span>
    <div class="rte2-group">
      <?php echo rte2_tool('justifyLeft', t('align_left'), $icoLeft); ?>
      <?php echo rte2_tool('justifyCenter', t('align_center'), $icoCenter); ?>
      <?php echo rte2_tool('justifyRight', t('align_right'), $icoRight); ?>
      <?php echo rte2_tool('justifyFull', t('align_justify'), $icoJustify); ?>
    </div>
    <span class="rte2-sep" aria-hidden="true"></span>
    <div class="rte2-group">
      <?php echo rte2_tool('clear', t('clear'), $icoClear); ?>
    </div>
  </div>

  <div class="rte2-body">
    <div class="rte2-doc">
      <div class="rte2-editor" contenteditable="true" role="textbox" aria-multiline="true"
           spellcheck="true" data-placeholder="<?php echo h(t('placeholder')); ?>"
           aria-label="<?php echo h(t('editor_aria')); ?>"></div>
    </div>
  </div>

  <footer class="rte2-status">
    <span class="rte2-dot" aria-hidden="true"></span>
    <span class="rte2-status-text" data-role="status"></span>
    <span class="rte2-spacer"></span>
    <span class="rte2-counts" data-role="counts"></span>
    <span class="rte2-fmt" data-role="format"></span>
  </footer>

  <div class="rte2-dialog" data-role="dialog" hidden>
    <div class="rte2-dialog-card" role="dialog" aria-modal="true">
      <div class="rte2-dialog-title" data-role="dlg-title"></div>
      <label class="rte2-dialog-label" data-role="dlg-label" for="rte2-dlg-input<?php echo h($AppID); ?>"></label>
      <input type="text" class="rte2-dialog-input" data-role="dlg-input" id="rte2-dlg-input<?php echo h($AppID); ?>"
             autocomplete="off" spellcheck="false">
      <div class="rte2-dialog-formats" data-role="dlg-formats" hidden>
        <label for="rte2-dlg-format<?php echo h($AppID); ?>"><?php echo h(t('save_as_format')); ?></label>
        <select class="rte2-select" data-role="dlg-format" id="rte2-dlg-format<?php echo h($AppID); ?>">
          <option value="html"><?php echo h(t('fmt_html')); ?></option>
          <option value="txt"><?php echo h(t('fmt_txt')); ?></option>
          <option value="md"><?php echo h(t('fmt_md')); ?></option>
        </select>
      </div>
      <div class="rte2-dialog-hint" data-role="dlg-hint"></div>
      <div class="rte2-dialog-error" data-role="dlg-error" hidden></div>
      <div class="rte2-dialog-actions">
        <button type="button" class="rte2-btn" data-role="dlg-cancel"></button>
        <button type="button" class="rte2-btn rte2-primary" data-role="dlg-ok"></button>
      </div>
    </div>
  </div>
</div>
<script type="application/json" class="rte2-boot" id="rte2-boot<?php echo h($AppID); ?>" data-root="rte2<?php echo h($AppID); ?>"><?php echo $bootJson; ?></script>
<script><?php echo $js; ?></script>
<?php
$AppContainer->EndContainer();
?>
