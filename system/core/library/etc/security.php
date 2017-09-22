<?
class security {
  function __encode($text, $key)
{
    $td = mcrypt_module_open ("tripledes", '', 'cfb', '');
    $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
    if (mcrypt_generic_init ($td, $key, $iv) != -1)
		{
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
session_start();
if(!isset($_SESSION['loginuser'])){
  require '../../core/library/etc.php';
  $infob = new info;
  $infob->writestat('ALARM! Unauthorized app launch access','../../core/journal.mcj');
  exit;
  ?>
  <script>document.body.innerHTML = '';</script>
  <?
}
}

function crypt($string, $salt){
  $crypt_string=addslashes(strip_tags(htmlspecialchars(crypt($string,'$2a$10$'.md5($salt)))));
  $crypt_string=str_replace('$2a$10$','',$crypt_string);
  return $crypt_string;
}
}
?>
