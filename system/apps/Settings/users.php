<?
//Инициализируем переменные
$appid=$_GET['appid'];
$erase=$_GET['erase'];
$appname=$_GET['appname'];
$folder=$_GET['destination'];
$selectuser=$_GET['selectuser'];
$deleteuser=$_GET['deleteuser'];
$adduserlogin=$_GET['adduserlogin'];
$adduserpassword=$_GET['adduserpassword'];
$adduserhdd=$_GET['adduserhdd'];
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
session_start();
$settingsbd = new readbd;
$gui = new gui;
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
  $adduserpassword=md5(addslashes(strip_tags(htmlspecialchars($adduserpassword))));
  $adduserhdd=intval($adduserhdd);
  $fuid=strtoupper(md5($reglogin.$regpassword.$reghdd.$date));
  $sql="CREATE TABLE IF NOT EXISTS forestusers (login VARCHAR(50), password VARCHAR(50), hdd INT(50), fuid VARCHAR(50));
  INSERT INTO forestusers (login, password,hdd,fuid) VALUES ('$adduserlogin', '$adduserpassword','$adduserhdd','$fuid')";
  try {
    $conn->exec($sql);
    //подготавливаем нового пользователя;
    mkdir('../../users/'.$adduserlogin.'/');
    mkdir('../../users/'.$adduserlogin.'/desktop/');
    mkdir('../../users/'.$adduserlogin.'/settings/');
    mkdir('../../users/'.$adduserlogin.'/settings/etc/');
    copy('../../core/design/walls/water.jpg','../../users/'.$adduserlogin.'/settings/etc/wall.jpg');
    copy('../../core/design/themes/original.fth','../../users/'.$adduserlogin.'/settings/etc/theme.fth');
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
  $sql="DELETE FROM forestusers WHERE login='$deleteuser'";
  if($conn->query($sql)){$faction = new fileaction; $faction->deleteDir($_SERVER['DOCUMENT_ROOT'].'/system/users/'.$deleteuser); $gui->newnotification($appname,"Учетные записи","Пользователь <b>$deleteuser</b> удален!");}else{$gui->newnotification($appname,"Учетные записи","Произошла ошибка при удалении");}
}

echo '</div></div><hr>';

unset($settingsbd,$conn,$sql);
?>

</div>
<script>
function back<?echo $appid;?>(el){$("#<?echo $appid;?>").load("<?echo $folder?>main.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid;?>")};
function seluser<?echo $appid;?>(el2){$("#<?echo $appid;?>").load("<?echo $folder?>users.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&selectuser="+el2.id+"")};
function deleteuser<?echo $appid;?>(el3){$("#<?echo $appid;?>").load("<?echo $folder?>users.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&deleteuser="+el3.id+"")};
function adduser<?echo $appid;?>(){$("#<?echo $appid;?>").load("<?echo $folder?>users.php?id=<?echo rand(0,10000).'&destination='.$folder.'&appname='.$appname.'&appid='.$appid?>&adduserlogin="+document.getElementById("<?echo $appid.'reglogin';?>").value+"&selectuser="+document.getElementById("<?echo $appid.'reglogin';?>").value+"&adduserpassword="+document.getElementById("<?echo $appid.'regpassword';?>").value+"&adduserhdd="+document.getElementById("<?echo $appid.'reghdd';?>").value+"")};
</script>
