<?

class PermissionRequest{

  function fileassociate($array, $appdestination, $requestname, $appname){
    $file = '../../core/extconfiguration.foc';
    if(!file_exists($file)){
      file_put_contents($file,"[AppExt]\n\r[AppKeys]\n\r");
    }
    $current = file_get_contents($file);

    if(!preg_match('/[AppExt]/i',$current)){
      file_put_contents($file, "[AppExt]\n\r");
    }
    if(!preg_match('/[AppKeys]/i',$current)){
      file_put_contents($file, "[AppKeys]\n\r");
    }

      $ini_array = parse_ini_file($file, true);
      if($ini_array!==''){
        $list = '';
        foreach ($array as $key)
        {
          $current = file_get_contents($file);
          $check_dest = $key.'=';
          $check_key = $key.'_key='.$requestname;
          if(!preg_match("/$check_key/i",$current)){
            $current = str_replace('[AppKeys]',"[AppKeys]\n\r".$key.'_key='.$requestname,$current);
            file_put_contents($file, $current);
          }
          if(!preg_match("/$check_dest/i",$current)){
            $current = str_replace('[AppExt]',"[AppExt]\n\r".$key.'='.$appdestination,$current);
            $list = $list.' <b>'.$key.'</b> | ';
            file_put_contents($file, $current);
          }
        }
        global $object, $language;
        $pubname  = str_replace("_"," ",$appname);
        if(!empty($list)){
          $object->newnotification($appname,"Ассоциация файлов","Приложение <b>$pubname</b> ассоциировала расширения: $list");
        }
      }
  }

}

?>
