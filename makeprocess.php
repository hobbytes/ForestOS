<?

if(!isset($_SESSION)){
  session_start();
}

if(isset($_SESSION['loginuser'])){
  /* если вошли на другом устройстве — закрываем оболочку и уводим на вход */
  require_once 'system/core/library/session.php';
  if (empty($_SESSION['is_authuser']) || $_SESSION['is_authuser'] !== true
      || !ForestSession::validate((string)$_SESSION['loginuser'], (string)($_SESSION['fo_session_token'] ?? ''))) {
    echo '<script>try{window.top.location=\'/?kicked=1\';}catch(e){window.location=\'/?kicked=1\';}</script>';
    exit;
  }
  require_once 'system/core/library/etc.php';
  require_once 'system/core/library/filesystem.php';
  $infob = new info;
  $fileaction = new fileaction;

  //определяем мобильное устройство
  $infob->ismobile();
  if($mobile == 'true'){
    $autohide = 'false';
    $top = '46px';
    $left = '0px';
    $maxwidthm = '1';
    $maxwidth = '100%';
  }else{
    $autohide = 'true';
    $top = '1'.rand(4,9).'%';
    $left = '1'.rand(4,9).'%';
    $maxwidth = '100%';
    $maxwidthm = '1';
  }

  $d = $_GET['d'];//  destination
  $i = $_GET['i'];// id process
  $p = $_GET['p'];// name of property
  $k = $_GET['k'];// key property
  $n = $_GET['n'];// name of process
  $d_ = $_GET['data'];// get data array

  function makeprocess($destination,  $idprocess,  $param,  $key,  $name,  $data = null){
    global $mobile, $top, $left, $maxwidth, $autohide, $maxwidthm, $fileaction, $infob;
    $__aw = @parse_ini_file($_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/settings/AppWindow.foc');
    $__ctrl = (isset($__aw['ControlMode']) && (string)$__aw['ControlMode'] === '2') ? 'controls-left' : 'controls-right';
    $folder = dirname($destination);
    $folder = stristr($folder, 'system/');
    $destination = stristr($destination, 'system/');
    $appicon = $fileaction->filehash($folder.'/app.png','false');
    $_appicon = stristr($appicon, '?',true); //???
    if (!is_file($_appicon)){
      $appicon = 'system/core/design/images/app.png';
    }

    if(empty($name)){
      $name = basename($destination);
    }

    $destination_ = $destination;
    if(!preg_match('/.php/',$destination_)){
      $destination_ = $destination_.'.php';
    }

    $__title = htmlspecialchars(str_replace('_',  ' ',  $name));
    $__app = basename(str_replace('\\', '/', (string)$folder));
    if(!preg_match('/^[A-Za-z0-9_\-]+$/', $__app)){ $__app = ''; }

    echo '
    <div id=app'.$idprocess.' tabindex="'.$idprocess.'" style="max-width:'.$maxwidth.'; position:absolute; left:'.$left.'; top:'.$top.'; overflow:hidden; outline: none !important;" data-app="'.$__app.'" data-dest="'.htmlspecialchars((string)$destination, ENT_QUOTES).'" data-title="'.htmlspecialchars((string)$__title, ENT_QUOTES).'" data-param="'.htmlspecialchars((string)$param, ENT_QUOTES).'" data-key="'.htmlspecialchars((string)$key, ENT_QUOTES).'" class="ui-widget-content window windownormal windowborder" wh="" ww="" applength-'.$idprocess.'="0">
    <div id=drag'.$idprocess.' class="dragwindow '.$__ctrl.'" title="'.$__title.'">
        <div class="process-title">
          <span class="process-title-icon" style="background-image: url('.$appicon.');"></span>
          <span class="process-title-text">'.$__title.'</span>
          <span class="process-refresh" title="Перезагрузить приложение (Ctrl+R)" onClick="$(\'.reload'.$idprocess.'\').trigger(\'click\'); return false;"></span>
        </div>
        <div class="appwindowbutton-container">
          <div class="appwindowbutton winbtn winbtn-min hidewindow'.$idprocess.'" title="Свернуть в плашку">
          </div>
          <div class="appwindowbutton winbtn winbtn-max" title="Развернуть / Восстановить" onClick="$(\'#drag'.$idprocess.'\').trigger(\'dblclick\');">
          </div>
          <div class="appwindowbutton winbtn winbtn-close close'.$idprocess.'" title="Закрыть">
          </div>
          <div class="appwindowbutton reload'.$idprocess.'" style="display:none;" aria-hidden="true"></div>
        </div>
    </div>
      <div id='.$idprocess.' class="blurwindowpassive hideallclass process-container" location="'.str_replace(array('//','php/'),array('/','php'),$destination_).'">
      </div>
      </div>
    </div>';
    ?>
    <div id="logic<?echo $idprocess;?>">
      <script async>
      $( function() {
        $(document).ready(function(){
          ProcessLogic(
            "<?echo $idprocess?>",
            "<?echo $name?>",
            "<?echo $destination?>",
            "<?echo $destination_?>",
            "<?echo $maxwidthm?>",
            "<?echo $folder?>",
            "<?echo $mobile?>",
            "<?echo $key?>",
            "<?echo $param?>",
            "<?echo $autohide?>",
            '<?echo $data?>'
          );
        });
      });
      </script>
      </div>
      <?
      // get status
      $infob->beacon();
  }

  makeprocess($d, $i, $p, $k, $n, $d_);
}else{
  header('Location: login.php');
  die();
}
?>
