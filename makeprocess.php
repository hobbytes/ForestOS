<?
session_start();
if(isset($_SESSION['loginuser'])){
  include 'system/core/library/etc.php';
  include 'system/core/library/gui.php';
  include 'system/core/library/filesystem.php';
  $infob = new info;
  $object = new gui;
  $fileaction = new fileaction;
  //определяем мобильное устройство
  $infob->ismobile();
  if($mobile=='true'){
    $click='click';
    $autohide='false';
    $top='30px';
    $left='0px';
    $maxwidthm='1';
    $maxwidth='100%';
  }else{
    $click='dblclick';
    $autohide='true';
    $top='1'.rand(4,9).'%';
    $left='1'.rand(4,9).'%';
    $maxwidth='95%';
    $maxwidthm='0.95';
  }

  $d=$_GET['d'];//  destination
  $i=$_GET['i'];// id process
  $p=$_GET['p'];// name of property
  $k=$_GET['k'];// key property
  $n=$_GET['n'];// name of process

  function makeprocess($destination,  $idprocess, $param, $key, $name){
    global $click,$top,$left,$maxwidth,$autohide,$object,$maxwidthm,$fileaction;
    $folder = dirname($destination);
    $folder = stristr($folder, 'system/');
    $destination = stristr($destination, 'system/');
    $appicon = $fileaction->filehash($folder.'/app.png','false');
    $_appicon = stristr($appicon, '?',true); //???
    if (!is_file($_appicon)){
      $appicon = 'system/core/design/images/app.png';
    }

    if(empty($name)){
      $name=basename($destination);
    }

    $destination_ =  $destination.$file;
    if(!preg_match('/.php/',$destination_)){
      $destination_ = $destination_.'.php';
    }
    $style="'display','block'";
    $style2="'display','none'";
    echo '
    <div id=app'.$idprocess.' style="max-width:'.$maxwidth.'; position:absolute; left:'.$left.'; top:'.$top.'; overflow:hidden;" class="ui-widget-content window windownormal windowborder" wh="" ww="">
    <div id=drag'.$idprocess.' class="ui-widget-header dragwindow">
        <div class="process-title" style="cursor:default; font-size:17px; margin:0 10px; text-overflow:ellipsis; overflow:hidden;">
          <div style="background-color:transparent;  background-image: url('.$appicon.'); background-size:cover; height:20px; width:20px; margin:0px 3px 0px 3px; float:left;">
          </div>
          '.str_replace('_',  ' ',  $name).'
        </div>
        <div class="appwindowbutton-container" style="margin:0 10px;">
          <div class="appwindowbutton ui-forest-blink" onClick=" $(';echo "'#"; echo 'process'.$idprocess; echo "'"; echo ').remove();';echo ' $(';echo "'#"; echo "app$idprocess"; echo "'"; echo ').css('; echo "$style2"; echo '); checkwindows();" style="background-color:#ed2020; cursor: default;">
          x
          </div>
          <div class="hidewindow'.$idprocess.' appwindowbutton ui-forest-blink" onClick="" style="background-color:#37a22e; cursor: default;">
          -
          </div>
          <div class="reload'.$idprocess.' appwindowbutton ui-forest-blink" onClick="" style="background-color:#e09100; cursor: default;">
          o
          </div>
        </div>
    </div>
      <div id='.$idprocess.' class="blurwindowpassive hideallclass process-container" location="'.str_replace(array('//','php/'),array('/','php'),$destination_).'">
      </div>
      </div>
    </div>';
    ?>
    <div id="logic<?echo $idprocess;?>">
      <script async>
      ProcessLogic(
        "<?echo $idprocess?>",
        "<?echo $name?>",
        "<?echo $destination?>",
        "<?echo $destination_?>",
        "<?echo $maxwidthm?>",
        "<?echo $folder?>",
        "<?echo $click?>",
        "<?echo $key?>",
        "<?echo $param?>",
        "<?echo $autohide?>"
      );
      </script>
      </div>
      <?
  }
  makeprocess($d, $i, $p, $k, $n);
}else{
  header('Location: login.php');
  die();
}
?>
