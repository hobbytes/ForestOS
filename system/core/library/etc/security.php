<?
class security {

  function __encode($text, $key){
    $td = mcrypt_module_open ("tripledes", '', 'cfb', '');
    $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
    if (mcrypt_generic_init ($td, $key, $iv) != -1){
      $enc_text=base64_encode(mcrypt_generic ($td,$iv.$text));
      mcrypt_generic_deinit ($td);
      mcrypt_module_close ($td);
      return $enc_text;
    }
  }

function __decode($text, $key){
        $td = mcrypt_module_open ("tripledes", '', 'cfb', '');
        $iv_size = mcrypt_enc_get_iv_size ($td);
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
        if (mcrypt_generic_init ($td, $key, $iv) != -1) {
          $decode_text = substr(mdecrypt_generic ($td, base64_decode($text)),$iv_size);
          mcrypt_generic_deinit ($td);
          mcrypt_module_close ($td);
          return $decode_text;
        }
      }

function appprepare(){
  if(!isset($_SESSION)){
    session_start();
  }

  if(!isset($_SESSION['loginuser'])){

    $backtrace = debug_backtrace();
    $File = $backtrace[1][file];

    if(!empty($File)){
      $File = ", FILE -> '$File'";
    }elseif (!empty($backtrace[0][file])) {
      $File = $backtrace[0][file];
      $File = ", FILE -> '$File'";
    }else{
      $File = '';
    }

    if(!empty($backtrace['1']['object']->AppNameInfo)){
      $AppName = ', APP NAME -> '.$backtrace['1']['object']->AppNameInfo;
    }else{
      $AppName = '';
    }

    require_once $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc.php';
    $infob = new info;
    $infob->writestat( "ALARM! Unauthorized app launch access$File$AppName", $_SERVER['DOCUMENT_ROOT'].'/system/core/journal.mcj');
    exit;
    ?>
    <script>document.body.innerHTML = '';</script>
    <?
  }
}

function crypt_s($string, $salt){
  $crypt_string=addslashes(strip_tags(htmlspecialchars(crypt($string,'$2a$10$'.md5($salt)))));
  $crypt_string=str_replace('$2a$10$','',$crypt_string);
  return $crypt_string;
}
}
?>
