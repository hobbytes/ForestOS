<?
//Инициализируем переменные
$appid=$_GET['appid'];
$appname=$_GET['appname'];
$folder=$_GET['destination'];
?>

<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<div style="width:100%; text-align:left; padding-bottom:10px; font-size:30px; border-bottom:#d8d8d8 solid 2px; text-overflow:ellipsis; overflow:hidden;">
<span onClick="back<?echo $appid;?>();" class="ui-forest" style="background-color:#d8d8d8; color:#000; border-radius:30%; cursor:pointer; font-size:25px; margin-left:5px;"> &#9668 </span>Учетные записи</div>
<?php
/*Settings*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/gui.php';
include '../../core/library/etc/security.php';
$security	=	new security;
session_start();
$security->appprepare();
$settingsbd = new readbd;
$gui = new gui;
$erase=$_GET['erase'];
$selectuser=$_GET['selectuser'];
$deleteuser=$_GET['deleteuser'];
$adduserlogin=$_GET['adduserlogin'];
$adduserpassword=$_GET['adduserpassword'];
$adduserhdd=$_GET['adduserhdd'];
$settingsbd->readglobal2("fuid","forestusers","login",$_SESSION["loginuser"]);
$fuid=$getdata;

echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">Учетные записи</b>';
echo '<div style="margin-top:10px; overflow:hidden;">';
/*-----loadusers----*/
$conn = new PDO (DB_DSN, DB_USERNAME, DB_PASSWORD);
$sql="SELECT login FROM forestusers";
$id=$conn->query($sql);
  while ($row = $id->fetch())
    {
      $getdata=$row['login'];
      echo '<div id="'.$getdata.'" onClick="seluser'.$appid.'(this);" class="userselect" style="background:#2e2f31; cursor:pointer; border-radius:40px;40px;40px;40px; width:80px; height:50px; font-size:20px; text-align:center; padding-top:30px; color:#fff; float:left; overflow:hidden; text-overflow:ellipsis; display:block; position:relative; margin-left:10px;">'.$getdata.'</div>';
    }

echo '<div id="newuser" onClick="seluser'.$appid.'(this);" class="userselect" style="background:#5ece5d; cursor:pointer; border:3px dashed #298c23; border-radius:40px;40px;40px;40px; width:74px; height:44px; font-size:18px; text-align:center; padding-top:30px; color:#fff; float:left; overflow:hidden; text-overflow:ellipsis; display:block; position:relative; margin-left:10px;">+</div>';


if($adduserlogin!='' && $adduserpassword!='' && $adduserhdd!='')
{
  $date= date("d.m.y,H:i:s");
  $adduserlogin=strtolower(addslashes(strip_tags(htmlspecialchars($adduserlogin))));
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
    copy('../../core/design/walls/water.jpg','../../users/'.$adduserlogin.'/settings/etc/wall.jpg');
    copy('../../core/design/themes/original.fth','../../users/'.$adduserlogin.'/settings/etc/theme.fth');
    $dr = $_SERVER['DOCUMENT_ROOT'];
    $userhash = md5($fuid.$dr.$adduserpassword);
    $content="[link]\n\rdestination=system/apps/Explorer/\n\rfile=main\n\rkey=dir\n\rparam=$dr/system/users/admin/trash\n\rname=Explorer\n\rlinkname=Корзина\n\ricon=system/apps/Explorer/assets/trashicon.png";
    file_put_contents('../../users/'.$adduserlogin.'/desktop/trash.link',$content);
    file_get_contents('http://forest.hobbytes.com/media/os/ubase/adduser.php?fuid='.$fuid.'&followlink='.$_SERVER['SERVER_NAME'].'&userhash='.$userhash.'');
  }
  catch (PDOException $e){
    echo 'false: '.$e->getMessage().'\n';
    die();
  }
  $selectuser=$adduserlogin;
}


/*-----check users----*/
if($selectuser!=''){
  if($selectuser!='newuser'){
  $settingsbd->readglobal2("fuid","forestusers","login",$selectuser);
  $fuid=$getdata;
  echo '<div style="text-align:left; margin-top:100px; "><b style="font-size:35px; text-transform:uppercase;">'.$selectuser.'</b>';
  echo '<div><br> FUID: '.$fuid.'</div></div>';
  echo '<div id="'.$selectuser.'" onClick="deleteuser'.$appid.'(this);" class="ui-forest-button ui-forest-cancel">Удалить пользователя</div>';
}
else
{
  echo '<div style="text-align:left; margin-top:100px; "><b style="font-size:25px; text-transform:uppercase;">Новый пользователь</b></div>';

echo "<div>Введите логин:</div>";
  $gui->inputslabel('Логин', 'text', ''.$appid.'reglogin', ''.$adduserlogin.'','50', 'введите логин');
echo "<div>Введите пароль:</div>";
  $gui->inputslabel('Пароль', 'password', ''.$appid.'regpassword', ''.$adduserpassword.'','50','придумайте пароль');
echo "<div>Укажите место на диске:</div>";
  $gui->inputslabel('Место на диске', 'text', ''.$appid.'reghdd', '60000' ,'50','укажите место на диске');
  echo '<div id="addbtnuser'.$appid.'" onClick="adduser'.$appid.'();" class="ui-forest-button ui-forest-accept">Добавить пользователя</div>';
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
      $e = file_get_contents('http://forest.hobbytes.com/media/os/ubase/deleteuser.php?fuid='.$fuid.'&followlink='.$dr.'&userhash='.$userhash.'');
      if($e=='true'){
        $faction = new fileaction;
        $faction->deleteDir($_SERVER['DOCUMENT_ROOT'].'/system/users/'.$deleteuser);
        $gui->newnotification($appname,"Учетные записи","Пользователь <b>$deleteuser</b> удален!");
        ?>
        <script>
        $("#<?echo $deleteuser?>").remove();
        </script>
        <?
      }
    }else{
      $gui->newnotification($appname,"Учетные записи","Произошла ошибка при удалении");
    }
  }else{
    $gui->newnotification($appname,"Учетные записи","Нельзя удалить учетную запись администратора");
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
function adduser<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder?>users.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&adduserlogin="+document.getElementById("<?echo $appid.'reglogin';?>").value+"&selectuser="+document.getElementById("<?echo $appid.'reglogin';?>").value+"&adduserpassword="+document.getElementById("<?echo $appid.'regpassword';?>").value+"&adduserhdd="+document.getElementById("<?echo $appid.'reghdd';?>").value+"")};
</script>
