<?
if (function_exists('date_default_timezone_set'))
date_default_timezone_set('Europe/Moscow');
class info{
    function browser($agent) {
    	preg_match("/(MSIE|Opera|Firefox|Chrome|Version|Opera Mini|Netscape|Konqueror|SeaMonkey|Camino|Minefield|Iceweasel|K-Meleon|Maxthon)(?:\/| )([0-9.]+)/", $agent, $browser_info); // регулярное выражение, которое позволяет отпределить 90% браузеров
            list(,$browser,$version) = $browser_info; // получаем данные из массива в переменную
            if (preg_match("/Opera ([0-9.]+)/i", $agent, $opera)) return 'Opera '.$opera[1]; // определение _очень_старых_ версий Оперы (до 8.50), при желании можно убрать
            if ($browser == 'MSIE') { // если браузер определён как IE
                    preg_match("/(Maxthon|Avant Browser|MyIE2)/i", $agent, $ie); // проверяем, не разработка ли это на основе IE
                    if ($ie) return $ie[1].' based on IE '.$version; // если да, то возвращаем сообщение об этом
                    return 'IE '.$version; // иначе просто возвращаем IE и номер версии
            }
            if ($browser == 'Firefox') { // если браузер определён как Firefox
                    preg_match("/(Flock|Navigator|Epiphany)\/([0-9.]+)/", $agent, $ff); // проверяем, не разработка ли это на основе Firefox
                    if ($ff) return $ff[1].' '.$ff[2]; // если да, то выводим номер и версию
            }
            if ($browser == 'Opera' && $version == '9.80') return 'Opera '.substr($agent,-5); // если браузер определён как Opera 9.80, берём версию Оперы из конца строки
            if ($browser == 'Version') return 'Safari '.$version; // определяем Сафари
            if (!$browser && strpos($agent, 'Gecko')) return 'Browser based on Gecko'; // для неопознанных браузеров проверяем, если они на движке Gecko, и возращаем сообщение об этом
            return $browser.' '.$version; // для всех остальных возвращаем браузер и версию
    }
    function writestat($alarmbody,$folder){

      include 'etc/security.php';
      include '../bd.php';
      global $getdata, $getstat;
      $security = new security;
      $bd = new readbd;
      $bd->readglobal2("password","forestusers","login",$_SESSION["loginuser"]);
      $key=$getdata;
      $date= date("d.m.y,H:i:s");
      $ip = $_SERVER["REMOTE_ADDR"];
      $browser  = $this->browser($_SERVER["HTTP_USER_AGENT"]);
      $text = $alarmbody.': ['.$date.'] browser:'.$browser.', ip:'.$ip;
      $this->readstat($folder);
      $content  = $getstat.PHP_EOL.$text;
      $text = $security->__encode($content, $key);
      file_put_contents($folder,$text);
    }

    function readstat($folder){
      session_start();
      include './etc/security.php';
      include '../bd.php';
      global $getdata, $getstat;
      $security = new security;
      $bd = new readbd;
      $bd->readglobal2("password","forestusers","login",$_SESSION["loginuser"]);
      $key=$getdata;
      $content  = file_get_contents($folder);
      $getstat = $security->__decode($content, $key);
    }

    function ismobile(){
      global $mobile;
$iphone = strpos($_SERVER['HTTP_USER_AGENT'],"iPhone");
$android = strpos($_SERVER['HTTP_USER_AGENT'],"Android");
$palmpre = strpos($_SERVER['HTTP_USER_AGENT'],"webOS");
$berry = strpos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
$ipod = strpos($_SERVER['HTTP_USER_AGENT'],"iPod");
$mobile = strpos($_SERVER['HTTP_USER_AGENT'],"Mobile");
$symb = strpos($_SERVER['HTTP_USER_AGENT'],"Symbian");
$operam = strpos($_SERVER['HTTP_USER_AGENT'],"Opera M");
$htc = strpos($_SERVER['HTTP_USER_AGENT'],"HTC_");
$fennec = strpos($_SERVER['HTTP_USER_AGENT'],"Fennec/");
$winphone = strpos($_SERVER['HTTP_USER_AGENT'],"WindowsPhone");
$wp7 = strpos($_SERVER['HTTP_USER_AGENT'],"WP7");
$wp8 = strpos($_SERVER['HTTP_USER_AGENT'],"WP8");
if ($ipad || $iphone || $android || $palmpre || $ipod || $berry || $mobile || $symb || $operam || $htc || $fennec || $winphone || $wp7 || $wp8 === true) {
    $mobile='true';
}else{
    $mobile='false';
}
    }
}
?>
