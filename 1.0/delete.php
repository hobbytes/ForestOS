<?
$dir2 = $_POST['deleteapp'];

function removeDirectory($dir) {
   if ($objs = glob($dir."/*")) {
      foreach($objs as $obj) {
        is_dir($obj) ? removeDirectory($obj) : unlink($obj);
      }
   }
   rmdir($dir);
 }
 removeDirectory($dir2);

?>
