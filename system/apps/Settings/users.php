<?
//Инициализируем переменные
$appid=$_GET['appid'];
$appname=$_GET['appname'];
$folder=$_GET['destination'];
session_start();
$language_users  = parse_ini_file('lang/users.lang');
?>

<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span><?echo $language_users[$_SESSION['locale'].'_settings_users']?></div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/gui.php';
include '../../core/library/etc/security.php';
$security	=	new security;
$security->appprepare();
$settingsbd = new readbd;
$gui = new gui;
$erase=$_GET['erase'];
$selectuser=$_GET['selectuser'];
$deleteuser=$_GET['deleteuser'];
$adduserlogin=$_GET['adduserlogin'];
$adduserpassword=$_GET['adduserpassword'];
$adduserhdd=$_GET['adduserhdd'];
$addrule_user = $_GET['addrule'];
$removerule_user = $_GET['removerule'];
$settingsbd->readglobal2("fuid","forestusers","login",$_SESSION["loginuser"]);
$fuid=$getdata;

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">'.$language_users[$_SESSION['locale'].'_settings_users'].'</b>';
echo '<div style="margin-top:10px; overflow:hidden;">';
/*-----loadusers----*/

if($_SESSION['loginuser'] == $_SESSION['superuser']){
  if(!empty($addrule_user)){
    $settingsbd->updatebd("forestusers",status,superuser,login,$addrule_user);
    $gui->newnotification($appname,$language_users[$_SESSION['locale'].'_settings_users'],  $language_users[$_SESSION['locale'].'_addrulenot'].": <b>".str_replace('_',' ', $addrule_user)."</b>");
  }
  if(!empty($removerule_user)){
    $settingsbd->updatebd("forestusers",status,normaluser,login,$removerule_user);
    $gui->newnotification($appname,$language_users[$_SESSION['locale'].'_settings_users'],  $language_users[$_SESSION['locale'].'_removerulenot'].": <b>".str_replace('_',' ', $removerule_user)."</b>");
  }
  $conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
  $sql="SELECT login FROM forestusers";
  $id=$conn->query($sql);
    while ($row = $id->fetch())
      {
        $getdata  = $row['login'];
        $pubname = str_replace('_', ' ', $getdata);
        echo '<div id="'.$getdata.'" onClick="seluser'.$appid.'(this);" class="userselect" style="background:#2e2f31; cursor:pointer; border-radius:40px;40px;40px;40px; width:80px; height:50px; font-size:20px; text-align:center; padding-top:30px; color:#fff; float:left; overflow:hidden; text-overflow:ellipsis; display:block; position:relative; margin-left:10px;" title="'.$pubname.'">'.$pubname.'</div>';
      }
      echo '<div id="newuser" onClick="seluser'.$appid.'(this);" class="userselect" style="background:#5ece5d; cursor:pointer; border:3px dashed #298c23; border-radius:40px;40px;40px;40px; width:74px; height:44px; font-size:18px; text-align:center; padding-top:30px; color:#fff; float:left; overflow:hidden; text-overflow:ellipsis; display:block; position:relative; margin-left:10px;">+</div>';
}else{
  $pubname = str_replace('_', ' ', $_SESSION['loginuser']);
  echo '<div id="'.$_SESSION['loginuser'].'" onClick="seluser'.$appid.'(this);" class="userselect" style="background:#2e2f31; cursor:pointer; border-radius:40px;40px;40px;40px; width:80px; height:50px; font-size:20px; text-align:center; padding-top:30px; color:#fff; float:left; overflow:hidden; text-overflow:ellipsis; display:block; position:relative; margin-left:10px;" title="'.$pubname.'">'.$pubname.'</div>';
}

if($adduserlogin!='' && $adduserpassword!='' && $adduserhdd!='' && $_SESSION['loginuser'] == $_SESSION['superuser'])
{
  $adduserlogin = strtolower(addslashes(strip_tags(htmlspecialchars($adduserlogin))));
  $adduserlogin = str_replace(' ','_',$adduserlogin);
  $usercheck = file_get_contents('http://forest.hobbytes.com/media/os/ubase/checkuser.php?check='.$adduserlogin);
  if($usercheck == 'true'){
    die($gui->errorLayot($language_users[$_SESSION['locale'].'_twinuser_error']));
  }
  $settingsbd->readglobal2("login","forestusers","login",$adduserlogin);
  if(empty($getdata)){
    $date= date("d.m.y,H:i:s");
    $adduserpassword=$security->crypt_s($adduserpassword,$adduserlogin);
    $adduserhdd=intval($adduserhdd);
    $fuid=strtoupper(md5($adduserlogin.$regpassword.$adduserpassword.$date));
    $sql="CREATE TABLE IF NOT EXISTS forestusers (login VARCHAR(150), password VARCHAR(150), hdd INT(150), fuid VARCHAR(150), status  VARCHAR(150));
    INSERT INTO forestusers (login, password, hdd,  fuid, status) VALUES ('$adduserlogin', '$adduserpassword',  '$adduserhdd',  '$fuid',  '0')";
    try {
      $conn->exec($sql);
      //подготавливаем нового пользователя;
      mkdir('../../users/'.$adduserlogin.'/');
      mkdir('../../users/'.$adduserlogin.'/desktop/');
      mkdir('../../users/'.$adduserlogin.'/trash/');
      mkdir('../../users/'.$adduserlogin.'/settings/');
      mkdir('../../users/'.$adduserlogin.'/settings/etc/');
      $wall=glob('../../core/design/walls/*.jpg');
      if(!empty($wall[0])){
        copy($wall[0],'../../users/'.$adduserlogin.'/settings/etc/wall.jpg');
      }
      file_put_contents('../../users/'.$adduserlogin.'/settings/language.foc',  $_SESSION['locale']);
      copy('../../core/design/themes/original.fth','../../users/'.$adduserlogin.'/settings/etc/theme.fth');
      $dr = $_SERVER['DOCUMENT_ROOT'];
      $userhash = md5($fuid.$dr.$adduserpassword);
      $content="[link]\n\rdestination=system/apps/Explorer/\n\rfile=main\n\rkey=dir\n\rparam=$dr/system/users/$adduserlogin/trash\n\rname=Explorer\n\rlinkname=Корзина\n\ricon=system/apps/Explorer/assets/trashicon.png";
      $os_info = parse_ini_file('../../core/osinfo.foc');
      file_put_contents('../../users/'.$adduserlogin.'/desktop/trash.link',$content);
      file_get_contents('http://forest.hobbytes.com/media/os/ubase/adduser.php?fuid='.$fuid.'&followlink='.$_SERVER['SERVER_NAME'].'&userhash='.$userhash.'&login='.$adduserlogin.'&version='.str_replace(' ','_',$os_info['codename'].$os_info['subversion']));
    }
    catch (PDOException $e){
      echo 'false: '.$e->getMessage().'\n';
      die();
    }
    $selectuser=$adduserlogin;
  }else{
    unset($selectuser, $adduserlogin, $adduserpassword, $adduserhdd);
    $gui->errorLayot($language_users[$_SESSION['locale'].'_twinuser_error']);
  }
}


/*-----check users----*/
if($selectuser!=''){
  if($selectuser!='newuser'){
  $settingsbd->readglobal2("fuid","forestusers","login",$selectuser);
  $fuid=$getdata;
  $pubusername = str_replace('_',' ',$selectuser);
  echo '<div style="text-align:left; margin-top:100px; "><b style="font-size:35px; text-transform:uppercase;">'.$pubusername.'</b>';
  echo '<div><br> FUID: '.$fuid.'</div></div><br>';
  if($_SESSION['loginuser'] == $_SESSION['superuser'] && $_SESSION['loginuser'] != $selectuser){
    $settingsbd->readglobal2("status","forestusers","login",$selectuser);
    if($getdata == 'superuser'){
      echo '<div id="'.$selectuser.'" onClick="removerule'.$appid.'(this);" class="ui-forest-button ui-forest-cancel">'.$language_users[$_SESSION['locale'].'_button_removerule'].'</div><br>';
    }else{
      echo '<div id="'.$selectuser.'" onClick="addrule'.$appid.'(this);" class="ui-forest-button ui-forest-accept">'.$language_users[$_SESSION['locale'].'_button_addrule'].'</div><br>';
    }
  }
  if($_SESSION['loginuser'] != $selectuser){
    echo '<div id="'.$selectuser.'" onClick="deleteuser'.$appid.'(this);" class="ui-forest-button ui-forest-cancel">'.$language_users[$_SESSION['locale'].'_button_deleteuser'].'</div>';
  }
}
else
{
  echo '<div style="text-align:left; margin-top:100px; "><b style="font-size:25px; text-transform:uppercase;">'.$language_users[$_SESSION['locale'].'_newuser_label'].'</b></div>';

echo "<div>".$language_users[$_SESSION['locale'].'_inputuser_label'].":</div>";
  $gui->inputslabel('', 'text', ''.$appid.'reglogin', ''.$adduserlogin.'','50', $language_users[$_SESSION['locale'].'_inputuser_label']);
echo "<div>".$language_users[$_SESSION['locale'].'_inputpass_label'].":</div>";
  $gui->inputslabel('', 'password', ''.$appid.'regpassword', ''.$adduserpassword.'','50',$language_users[$_SESSION['locale'].'_inputpass_label']);
echo "<div>".$language_users[$_SESSION['locale'].'_inputcapacity_label'].":</div>";
  $gui->inputslabel('', 'text', ''.$appid.'reghdd', '1024' ,'50',$language_users[$_SESSION['locale'].'_inputcapacity_label']);
  echo '<div id="addbtnuser'.$appid.'" onClick="adduser'.$appid.'();" class="ui-forest-button ui-forest-accept">'.$language_users[$_SESSION['locale'].'_button_adduser'].'</div>';
}
}

if($deleteuser!=''){
  $settingsbd->readglobal2("status","forestusers","login",$deleteuser);
  if($getdata!='superuser'){
    $sql="DELETE FROM forestusers WHERE login='$deleteuser'";
    $fuid = $_GET['fuid'];
    $dr = $_SERVER['DOCUMENT_ROOT'];
    $settingsbd->readglobal2("password","forestusers","login",$deleteuser);
    $userhash = md5($fuid.$dr.$getdata);
    if($conn->query($sql)){
      $e = file_get_contents('http://forest.hobbytes.com/media/os/ubase/deleteuser.php?fuid='.$fuid.'&followlink='.$_SERVER['SERVER_NAME'].'&userhash='.$userhash.'');
      if($e=='true'){
        $faction = new fileaction;
        $faction->deleteDir($_SERVER['DOCUMENT_ROOT'].'/system/users/'.$deleteuser);
        $gui->newnotification($appname,$language_users[$_SESSION['locale'].'_settings_users'],  $language_users[$_SESSION['locale'].'_deleteusernot'].": <b>".str_replace('_',' ',$deleteuser)."</b>");
        ?>
        <script>
        $("#<?echo $deleteuser?>").remove();
        </script>
        <?
      }
    }else{
      $gui->newnotification($appname,$language_users[$_SESSION['locale'].'_settings_users'],  $language_users[$_SESSION['locale'].'_user_error']);
    }
  }else{
    $gui->newnotification($appname,$language_users[$_SESSION['locale'].'_settings_users'],  $language_users[$_SESSION['locale'].'_user_error_2']);
  }
}

echo '</div></div><hr>';

unset($settingsbd,$conn,$sql);
?>

</div>
<script>
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function seluser<?echo $appid;?>(el2){$("#<?echo $appid;?>").load("<?echo $folder?>users.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&selectuser="+el2.id+"")};
function deleteuser<?echo $appid;?>(el3){$("#<?echo $appid;?>").load("<?echo $folder?>users.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&deleteuser="+el3.id+"&fuid=<?echo $fuid?>")};
function addrule<?echo $appid;?>(el4){$("#<?echo $appid;?>").load("<?echo $folder?>users.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&selectuser="+el4.id+"&addrule="+el4.id+"&fuid=<?echo $fuid?>")};
function removerule<?echo $appid;?>(el5){$("#<?echo $appid;?>").load("<?echo $folder?>users.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&selectuser="+el5.id+"&removerule="+el5.id+"&fuid=<?echo $fuid?>")};

function adduser<?echo $appid;?>(){
  var u_login = escape($('.<?echo $appid?>reglogin').val());
  var u_password = escape($('.<?echo $appid?>regpassword').val());
  var u_hdd = $('.<?echo $appid?>reghdd').val();
  if(u_login && u_password && u_hdd){
    $("#<?echo $appid;?>").load("<?echo $folder?>users.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&adduserlogin="+u_login+"&selectuser="+u_login+"&adduserpassword="+u_password+"&adduserhdd="+u_hdd+"");
  }else{
    if(!u_hdd){
      $('.<?echo $appid?>reghdd').focus();
    }
    if(!u_password){
      $('.<?echo $appid?>regpassword').focus();
    }
    if(!u_login){
      $('.<?echo $appid?>reglogin').focus();
    }
  }
};

</script>
