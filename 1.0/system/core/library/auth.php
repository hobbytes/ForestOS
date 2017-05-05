<?
/*FOREST AUTH*/
class AuthClassUser {
    private $_login;
  	private $_password;

  public function construct($what,$type){
      $bds= new readbd;
  		global $getdata;
  		$bds->readglobalfunction(login,users,$what,$type);
  		$this->_login=$getdata;
  		$bds->readglobalfunction(password,users,$what,$type);
  		$this->_password=$getdata;
  	}
      public function isAuth() {
          if (isset($_SESSION["is_authuser"])) {
              return $_SESSION["is_authuser"];
          }
          else return false;
      }

      /**
       * @param string $login
       * @param string $passwors
       */
      public function auth($login, $passwors) {
          if ($login == $this->_login && $passwors == $this->_password) { $_SESSION["is_authuser"] = true; $_SESSION["loginuser"] = $login;
              return true;
          }
          else {$_SESSION["is_authuser"] = false;
              return false;
          }
      }

      public function getLogin() {
          if ($this->isAuth()) {
              return $_SESSION["loginuser"];
          }
      }


      public function out() {
          $_SESSION = array();
          session_destroy();
      }

      function checkout(){
        global $infob,$login_get,$action,$login,$auth;
        $login_get=$_GET['login'];
        $action=$_GET['action'];
        $login=$_SESSION["loginuser"];
        if ($_GET["action"] == 'logout')
        {
          $date= date("d.m.y,H:i:s");
          $ip=$_SERVER["REMOTE_ADDR"];
          $browser=$infob->browser($_SERVER["HTTP_USER_AGENT"]);
          $text='logout:['.$date.'], browser:'.$browser.', ip:'.$ip;
          $infob->writestat('system/users/'.$_SESSION["loginuser"].'/settings/login.stat',$text);
          $auth->out(); header("Location: ?exit=0");
        }
      }
  }
  unset($bds);
?>
