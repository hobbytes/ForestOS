<?
class AppInfo{
  function setInfo($name, $version, $author, $secondname){
    $arrayInfo = array('name' => $name, 'version' => $version, 'author' => $author, 'secondname' => $secondname);
    print_r(json_encode($arrayInfo));
    die();
  }
}
?>
