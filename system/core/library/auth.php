<?
/*FOREST AUTH*/
class AuthClassUser {
    private $_login;
  	private $_password;

  public function construct($what, $type, $keyaccess = NULL){
      $bds = new readbd;
  		//global $getdata;
      if(empty($keyaccess)){
    		$this->_login = $bds->readglobal2("login", "forestusers", "login", $type, true);
    		$this->_password = $bds->readglobal2("password", "forestusers", "login", $type, true);
      }else{
        $row = $this->byKey($keyaccess);
        $this->_login = $row ? (string)$row['login'] : '';
        $this->_password = $row ? (string)$row['password'] : '';
      }
  	}

  /**
   * Точный поиск по ключу доступа. TempKey хранится как «[ключ1][ключ2]».
   * Раньше здесь был SQL LIKE '%ключ%', из-за чего ключ «%» совпадал с любой
   * записью и пускал в чужую учётную запись без пароля.
   */
  private function byKey($keyaccess){
      $keyaccess = (string)$keyaccess;
      if($keyaccess === ''){
          return null;
      }
      try {
          $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
          $st = $pdo->prepare('SELECT login, password, TempKey FROM forestusers WHERE TempKey LIKE ?');
          $st->execute(array('%' . $keyaccess . '%'));
          foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
              if (preg_match_all('/\[([^\]]+)\]/', (string)$row['TempKey'], $m)) {
                  foreach ($m[1] as $k) {
                      if (hash_equals((string)$k, $keyaccess)) {
                          return $row;
                      }
                  }
              }
          }
      } catch (Throwable $e) {
      }
      return null;
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

          require_once __DIR__ . '/bans.php';
          $ip = ForestBans::ip();
          $ban = ForestBans::check($ip);
          if (!empty($ban['banned'])) {
            $_SESSION['is_authuser'] = false;
            $_SESSION['fo_ban_left'] = (int)$ban['left'];
            return false;
          }

          $ok = $this->authInner($login, $password, $keyaccess);
          if ($ok) {
            unset($_SESSION['fo_ban_left']);
            ForestBans::succeed($ip);
          } else {
            $st = ForestBans::fail($ip, (string)$login);
            $_SESSION['fo_ban_left'] = !empty($st['banned']) ? (int)$st['left'] : 0;
          }
          return $ok;
      }

      private function authInner($login, $password, $keyaccess = NULL) {

          if(!empty($keyaccess)){
            $row = $this->byKey($keyaccess);
            if(!$row){
              global $infob;
              if (isset($infob) && method_exists($infob, 'writestat')) {
                $infob->writestat('WARNING! Wrong Access Key -> '.$keyaccess, 'system/core/journal.mcj');
              }
              $_SESSION["is_authuser"] = false;
              return false;
            }
            $login = (string)$row['login'];
            $password = (string)$row['password'];
          }

          /* заблокированная учётная запись не может войти */
          if (!empty($login)) {
            try {
              $__bds = new readbd;
              $__blocked = $__bds->readglobal2("blocked", "forestusers", "login", $login, true);
              if ((string)$__blocked === '1') {
                global $infob;
                if (isset($infob) && method_exists($infob, 'writestat')) {
                  $infob->writestat('Blocked login attempt -> ' . $login, 'system/core/journal.mcj');
                }
                $_SESSION["is_authuser"] = false;
                return false;
              }
            } catch (Throwable $e) {
              /* колонки ещё нет — считаем, что блокировки нет */
            }
          }

          if ($login == $this->_login && $password == $this->_password) {
            $_SESSION["is_authuser"] = true;
            $_SESSION["loginuser"] = $login;
            /* новая сессия после входа — защита от фиксации */
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_regenerate_id(true);
            }
            $forwardedFor = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
            $_SESSION["CookieIsMine"] = md5($login.$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR'].$forwardedFor);

            if(!empty($keyaccess)){

              $bds = new readbd;
              //global $getdata;

              $TempKeyArray = (string)$bds->readglobal2("TempKey", "forestusers", "login", $login, true);

              /* вычёркиваем ровно тот ключ, которым вошли (раньше — regexp по вводу) */
              $left = array();
              if (preg_match_all('/\[([^\]]+)\]/', $TempKeyArray, $m)) {
                foreach ($m[1] as $k) {
                  if (!hash_equals((string)$k, (string)$keyaccess)) {
                    $left[] = '[' . $k . ']';
                  }
                }
              }
              $newKeys = implode('', $left);
              if ($newKeys !== $TempKeyArray) {
                try {
                  $pdo = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
                  $pdo->prepare('UPDATE forestusers SET TempKey = ? WHERE login = ?')
                      ->execute(array($newKeys, $login));
                } catch (Throwable $e) {
                }
              }

            }

            return true;
          }
          else { $_SESSION["is_authuser"] = false;
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
