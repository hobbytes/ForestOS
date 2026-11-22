<?php
class security {

  private function normalizeKey($key) {
    return hash('sha256', (string)$key, true);
  }

  function __encode($text, $key){
    $iv = random_bytes(16);
    $cipher = openssl_encrypt((string)$text, 'AES-256-CBC', $this->normalizeKey($key), OPENSSL_RAW_DATA, $iv);
    return base64_encode('FOS2' . $iv . $cipher);
  }

  function __decode($text, $key){
    $raw = base64_decode((string)$text, true);
    if ($raw === false || strlen($raw) < 20) {
      return '';
    }
    if (substr($raw, 0, 4) === 'FOS2') {
      $iv = substr($raw, 4, 16);
      $cipher = substr($raw, 20);
      $plain = openssl_decrypt($cipher, 'AES-256-CBC', $this->normalizeKey($key), OPENSSL_RAW_DATA, $iv);
      return $plain === false ? '' : $plain;
    }
    // Old mcrypt payloads cannot be decoded on PHP 8 without mcrypt.
    return '';
  }

  function appprepare(){
    if(!isset($_SESSION)){
      session_start();
    }

    if(!isset($_SESSION['loginuser'])){
      $backtrace = debug_backtrace();
      $File = $backtrace[1]['file'] ?? ($backtrace[0]['file'] ?? '');
      $File = $File ? ", FILE -> '$File'" : '';
      $AppName = '';
      if(!empty($backtrace[1]['object']->AppNameInfo)){
        $AppName = ', APP NAME -> '.$backtrace[1]['object']->AppNameInfo;
      }

      if (file_exists($_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc.php')) {
        require_once $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc.php';
        $infob = new info;
        if (method_exists($infob, 'writestat')) {
          @$infob->writestat("ALARM! Unauthorized app launch access$File$AppName", $_SERVER['DOCUMENT_ROOT'].'/system/core/journal.mcj');
        }
      }
      exit;
    }
  }

  function crypt_s($string, $salt){
    $crypt_string = crypt((string)$string, '$2a$10$'.md5((string)$salt));
    $crypt_string = str_replace('$2a$10$','',$crypt_string);
    return addslashes(strip_tags(htmlspecialchars($crypt_string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
  }
}
?>
