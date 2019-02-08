<?php

/**
 * Mercury GUI library for Forest OS
 * =================================
 * Version: 1.0
 * Author: Vyacheslav Gorodilov
**/

class AppGUI {

/* Select file from Explorer */
  function SelectFile($AppID, $Name, $Size, $ShowOnly = null, $PlaceHolder = null, $ButtonCaption = "Select File"){
    $Name = mb_strtolower($Name);
    echo '<div class="AppGUIElement'.$AppID.'" style="display: grid; margin: 10px 0; grid-template-columns: 70% 30%; width:'.$Size.';">';
    echo '<input id="'.$Name.$AppID.'" class="SelectFile'.$AppID.'" filesource_'.$Name.$AppID.'="none" placeholder="'.$PlaceHolder.'" type="text" style="width: 100%; height: 100%; -webkit-appearance:none; padding:10px; font-size:15px; border-radius:6px; margin:auto; background-color:#fff; float:none; border:1px solid #ccc; color:#3a3a3a;">';
    echo '<div class="ui-forest-blink" onClick="CallbackSelectFile'.$AppID.'(\'filesource_'.$Name.$AppID.'\', \''.$ShowOnly.'\')" style="background: #ff5655; width: 90%; padding: 10px 5px; margin: 0 5px; text-align: center; color: #fff; border-radius: 5px; cursor: pointer; word-break: break-word;">'.$ButtonCaption.'</div>';
    echo '</div>';

  }

/* Select file Callback */
  function CallbackSelectFile($AppID){

    echo '/* function CallbackSelectFile'.$AppID.' */';
    echo 'function CallbackSelectFile'.$AppID.'(CallbackObject, ShowOnly){';
    echo 'data = { callback:CallbackObject, showonly:ShowOnly };';
    echo 'makeprocess(\'system/apps/Explorer/main.php\', \'selector\', \'explorermode\', \'Explorer\', JSON.stringify(data));}';

    }

  }


?>
