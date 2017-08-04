<?
class filecalc{
function format_size($path){
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
  $size = 0;
  foreach ($it as $fi) {
      $size += $fi->getSize();}
         $metrics[0] = 'байт';
         $metrics[1] = 'Кбайт';
         $metrics[2] = 'Мбайт';
         $metrics[3] = 'Гбайт';
         $metrics[4] = 'Тбайт';
         $metric = 0;
         while(floor($size/1024) > 0){
             ++$metric;
             $size /= 1024;
         }
         $ret =  round($size,1)." ".(isset($metrics[$metric])?$metrics[$metric]:'??');
        echo $ret;
    }


function format($size){
  global $format;

  $filesizename = array( " Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB" );
  	return $size ?
  	$format= 	round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] :
  		'0 ' . $filesizename[0];

    }

function size_check($path){
  global $size;
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
  $size = 0;
  foreach ($it as $fi) {
      $size += $fi->getSize();}
    }
    }

    class fileaction{

      function rmdir_recursive($dir) {
        $trashfolder='../../users/'.$_SESSION["loginuser"].'/trash/';
        if(!is_dir($trashfolder)){mkdir($trashfolder);}

        if (is_file($dir)){
        if(copy($dir,$trashfolder.basename($dir))){unlink($dir); echo '  файл: <b>'.basename($dir).'</b> перемещен в корзину';}

        }else{
          $folder=basename($dir);
          if(is_dir($trashfolder)){$trashfolder=$trashfolder.$folder.rand(0,1000);}else{$trashfolder=$trashfolder.$folder;}rename($dir,$trashfolder); echo '  папка: <b>'.$folder.'</b> перемещена в корзину';
        }
    }

    public static function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            self::deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

    function filehash($filehashdest){
      global $object;
      if (file_exists($filehashdest)){
    return $filehashdest.'?h='.md5(date("dmyhis",filemtime($filehashdest)));}
    else{$object->dialog("Файл <b>$filehashdest</b> не существует!","Function error: ".__FUNCTION__."","bounce");
}
    }

    function rcopy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

  }

?>
