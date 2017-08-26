<?

class PermissionRequest{

  function fileassociate($array, $appdestination, $requestname, $appname){
    $file = '../../core/extconfiguration.foc';
    $check = file_get_contents($file);
    if(!eregi($appdestination, $check) || !eregi($requestname, $check)){
      $ini_array = parse_ini_file($file, true);
      if($ini_array!==''){
        $list = '';
        foreach ($array as $key)
        {
          $list = $list.' <b>*.'.$key.'</b>|';
          $current = file_get_contents($file);
          $current = str_replace('[AppExt]',"[AppExt]\n\r".$key.'='.$appdestination,$current);//что тут происходит?
          $current = str_replace('[AppKeys]',"[AppKeys]\n\r".$key.'_key='.$requestname,$current);
          file_put_contents($file, $current);
        }
        global $object;
        $pubname  = str_replace("_"," ",$appname);
        $object->newnotification($appname,"Ассоциация файлов","Приложение <b>$pubname</b> ассоциировала расширения: $list");
      }
    }
  }

}

?>
