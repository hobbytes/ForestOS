<?
/*FOREST AUTH*/
class AuthClassUser {
    private $_login;
  	private $_password;

  public function construct($what, $type, $keyaccess = NULL){
      $bds = new readbd;
  		global $getdata;
      if(empty($keyaccess)){
    		$bds->readglobalfunction('login', 'users', $what, $type);
    		$this->_login = $getdata;
    		$bds->readglobalfunction('password', 'users', $what, $type);
    		$this->_password = $getdata;
      }else{
        $this->_login = $bds->readglobal2("login", "forestusers", "TempKey", $keyaccess, true, true);
        $this->_password = $bds->readglobal2("password", "forestusers", "TempKey", $keyaccess, true, true);
      }
  	}

      public function isAuth() {
          if (isset($_SESSION["is_authuser"])) {
              return $_SESSION["is_authuser"];
          }
          else return false;
      }


      /**
       * @param string $login
       * @param string $password
       */
      public function auth($login, $password, $keyaccess = NULL) {

          if(!empty($keyaccess)){
            $bds = new readbd;
            global $getdata;
            $login = $bds->readglobal2("login", "forestusers", "TempKey", $keyaccess, true, true);
            $password = $bds->readglobal2("password", "forestusers", "TempKey", $keyaccess, true, true);
            if(empty($login) && empty($password)){
              global $infob;
              $infob->writestat('WARNING! Wrong Access Key -> '.$keyaccess, 'system/core/journal.mcj');
              $login = 'wrong access key!';
              $password = '0';
            }
          }

          if ($login == $this->_login && $password == $this->_password) {
            $_SESSION["is_authuser"] = true;
            $_SESSION["loginuser"] = $login;

            if(!empty($keyaccess)){

              $bds = new readbd;
              global $getdata;

              $TempKeyArray = $bds->readglobal2("TempKey", "forestusers", "login", $login, true);

              $get_keys = explode("[", $TempKeyArray);

              foreach ($get_keys as $key) {
                if(!empty($key)){
                  $key = str_replace(']', '', $key);
                  if(preg_match("/$keyaccess/", $key)){
                    $_TempKeyArray = str_replace('['.$key.']', '', $TempKeyArray);
                    if($_TempKeyArray != $TempKeyArray){
                      $bds->updatebd("forestusers", "TempKey", $_TempKeyArray, "login", $login);
                    }
                  }
                }
              }

            }

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
        global $infob, $login_get, $action, $login, $auth;
        if(isset($_GET['login'])){
          $login_get = $_GET['login'];
        }

        if(isset($_GET['action'])){
          $action = $_GET['action'];
        }

        if(isset($_SESSION["loginuser"])){
          $login = $_SESSION["loginuser"];
        }

        if (isset($_GET['action']) && $_GET["action"] == 'logout')
        {
          $infob->writestat('Success Logout -> '.$login, 'system/core/journal.mcj');
          $auth->out();
          header("Location: ?exit=0");
        }
      }
  }
  unset($bds);
?>
