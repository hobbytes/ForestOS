<?
if (function_exists('date_default_timezone_set'))
date_default_timezone_set('Europe/Moscow');
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

function strToHex($string)
{
    $hex='';
    for ($i=0; $i < strlen($string); $i++)
    {
        $hex .= dechex(ord($string[$i]));
    }
    return $hex;
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

function hexToStr($hex)
{
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2)
    {
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}

function appprepare(){
session_start();
if(!isset($_SESSION['loginuser'])){
  include '../../core/library/etc.php';
  $infob = new info;
  $date= date("d.m.y,H:i:s");
  $ip=$_SERVER["REMOTE_ADDR"];
  $browser=$infob->browser($_SERVER["HTTP_USER_AGENT"]);
  $text = 'ALARM! Unauthorized app launch access: ['.$date.'] browser:'.$browser.', ip:'.$ip;
  $infob->writestat('../../users/admin/settings/login.stat',$text);
  exit;
  ?>
  <script>document.body.innerHTML = '';</script>
  <?
}
}
}
?>
