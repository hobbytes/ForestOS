<?php

class Dock {

  function CreateNewDock(){

    if(!isset($_SESSION)){
      session_start();
    }

    if(isset($_SESSION['loginuser'])){

      $dir_ = $_SERVER['DOCUMENT_ROOT'];

      $dir = $dir_.'/system/users/'.$_SESSION["loginuser"].'/settings/Dock/';
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

      $CountFolders = count( glob("$dir/*", GLOB_ONLYDIR) ); // > 1
      $Temp = "";
      $SeparatorCount = 0;

      echo '<div class="dock-container topbartheme">';
      foreach (glob($dir.'*/*') as $object)
      {
        $object = pathinfo($object);
        $ObjectName = $object['filename'];
        $ObjectExt = $object['extension'];
        $ObjectApp = $dir_."/system/apps/$ObjectName/main.php";
        if(is_file($ObjectApp)){

          if($CountFolders >= 2 && $Temp != $object['dirname']){
            $SeparatorCount++;
            if($SeparatorCount > 1){
              echo '<span class="dock-separator"></span>';
            }
            $Temp = $object['dirname'];
          }

          $Action = "makeprocess('$dir_/system/apps/$ObjectName/main.php', '$ObjectName', '', '$ObjectName');";
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
