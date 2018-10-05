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

      function rmdir_recursive($dir,$trashfolder) {
        $d_root = $_SERVER['DOCUMENT_ROOT'];
        if($dir != $d_root.'/' && $dir != $d_root && $dir != '../../..//' && $dir != $d_root.'/system' && $dir != $d_root.'/system/users'  && $dir != $d_root.'/system/core' && $dir != $d_root.'/system/apps'  && !preg_match('/os.php/',$dir) && !preg_match('/login.php/',$dir) && !preg_match('/makeprocess/',$dir)){
          if(empty($trashfolder)){
                    $trashfolder=$d_root.'/system/users/'.$_SESSION["loginuser"].'/trash/';
          }
          if(!is_dir($trashfolder)){mkdir($trashfolder);}
          if (is_file($dir)){
          if(copy($dir,$trashfolder.basename($dir))){
            unlink($dir);
          }
          }else{
            $folder=basename($dir);
            if(is_dir($trashfolder)){
              $trashfolder = $trashfolder.$folder.rand(0,1000);
            }else{
              $trashfolder = $trashfolder.$folder;
            }
            rename($dir,$trashfolder);
            echo '  папка: <b>'.$folder.'</b> перемещена в корзину';
          }
        }else{
          echo 'error!';
        }
    }

    public static function deleteDir($dirPath) {
      $d_root = $_SERVER['DOCUMENT_ROOT'];
      if($dirPath != $d_root.'/' && $dirPath != $d_root && $dirPath != '../../..//' && $dirPath != $d_root.'/system' && $dirPath != $d_root.'/system/users'  && $dirPath != $d_root.'/system/core' && $dirPath != $d_root.'/system/apps'){
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
      }else{
        throw new InvalidArgumentException("can't delete this folder: $dirPath");
      }

}

    function filehash($filehashdest){
      if (file_exists($filehashdest)){
        return $filehashdest.'?h='.md5(date("dmyhis",filemtime($filehashdest)));
      }
    }

    function rcopy($src, $dst, $flag) {
      if(is_file($src)){
        copy($src,$dst.basename($src));
      }else{
        if(!empty($flag) && $flag == '1'){
          $realdir = pathinfo(realpath($src));
          $realdir = $realdir['basename'].'/';
        }else{
          $realdir = '';
        }
        $dir = opendir($src);
        @mkdir($dst.'/'.$realdir);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    recurse_copy($src . '/' . $file,$dst . '/' . $realdir. $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $realdir. $file);
                }
            }
        }
        closedir($dir);
      }
    }
    function makelink($linkdestination,$appdest,$appfile,$key,$param,$appname,$linkname,$icon){
      if($linkname != "null"){
          $content="[link]\ndestination=$appdest\nfile=$appfile\nkey=$key\nparam=$param\nname=$appname\nlinkname=$linkname\nicon=$icon";
          file_put_contents($linkdestination,$content);
          unset($linkdestination,$content);
          ?>
          <script>
            UpdateDesktop();
          </script>
          <?
      }
    }
  }

?>
