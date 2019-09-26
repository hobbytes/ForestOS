<?php

class Dock {

  function CreateNewDock(){

    if(!isset($_SESSION)){
      session_start();
    }

    if(isset($_SESSION['loginuser'])){
      $dir = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION["loginuser"].'/settings/Dock/';
      if(!is_dir($dir))
      {
        mkdir($dir);
      }
      echo '<div class="dock-container topbartheme">';
      foreach (glob($dir.'*') as $object)
      {
        $object = pathinfo($object);
        $ObjectName = $object['filename'];
        $ObjectExt = $object['extension'];
        $ObjectApp = "/home/u588238148/public_html/forestos/system/apps/$ObjectName/main.php";
        if(is_file($ObjectApp)){
          $Action = "makeprocess('/home/u588238148/public_html/forestos/system/apps/$ObjectName/main.php', '$ObjectName', '', '$ObjectName');";
          echo '<div class="dock-icon" onClick="'.$Action.'" style="background-image: url(./system/apps/'.$ObjectName.'/app.png?h=1ca7108a11d44b16d38fce45ef19670b);">';
          echo '</div>';
        }
      }
      if(!$_SESSION['is_mobile']){
      ?>
        <script src="system/core/library/js/dock-scroll.js"></script>
      <?
      }
      echo '</div>';

    }

  }

}

?>
