<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-height:95%; max-width:100%; width:800px; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*Explorer*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/gui.php';
//Инициализируем переменные
global $idp;
$fo = new filecalc;
$faction = new fileaction;
$dir = $_GET['dir'];
$del = $_GET['del'];
$deleteforever = $_GET['delf'];
$link=$_GET['linkdir'];
$linkname=$_GET['linkname'];
$ico=$_GET['ico'];
$click=$_GET['mobile'];
$folder=$_GET['destination'];
$dialogexplorer = new gui;

if (isset($_GET['makedir'])){
	if(!is_dir($dir.'/'.$_GET['makedir']))
	{
		if(mkdir($dir.'/'.$_GET['makedir'],0755)){
			echo "Папка ".$_GET['makedir']." создана!";
		}else{
			echo "Папка ".$_GET['makedir']." не создана!";
		}
	}else{
		echo 'Такая папка уже существует!';
	}
}
//Запускаем сессию
session_start();
//обрабатываем кнопки удаления и перемещения в корзину
if($del!=''){
	$faction->rmdir_recursive($del);
}
if($deleteforever!=''){
	$faction->deleteDir($deleteforever);
}
//Логика
if($link!=''){
	if($linkname=='main.php'){
		$destination=$link;
		$link='';
		$param='';
		$newname=stristr($destination, 'apps/');
		$newname=str_replace(array('apps/','/main.php'),'',$newname);
		$puplicname=$newname;
	}else{
		$param='dir';
		$destination="system/apps/Explorer/";
		$puplicname=$linkname;
		$newname='Explorer';
	}
	$content="[link]\ndestination=$destination\nfile=main\nkey=$param\nparam=$link\nname=$newname\nlinkname=$puplicname\nicon=$ico";
	$myfile=fopen('../../users/'.$_SESSION["loginuser"].'/desktop/'.$puplicname.'_'.uniqid().'.link',"w");
	fwrite($myfile,$content);
	fclose($myfile);
}

if ($dir==''){
	$dir='../../../';
}
if(!is_dir($dir)){
	$ext=pathinfo($dir);
	$ext=$ext['extension'];
	if($ext=='php'){
		$file=basename($dir,'.php');
		$dest=$dir;
		$dir=dirname($dir);
		$param='';
		$keys='';
	}else{
		$ini_array=parse_ini_file("../../core/extconfiguration.foc");
		$dest=$ini_array[$ext];
		$param=substr($dir,strpos($dir,'os')+strlen('os'));
		$keys=$ini_array[$ext.'_key'];
		$dir=dirname($dir);
	}
	if ($dest!=''){
		?><script>makeprocess2('<?echo $dest?>','<?echo $param;?>','<?echo $keys;?>');</script>
		<?}else{
			$dialogexplorer->dialog("Не могу открыть файл с расширением <b style='text-transform:uppercase;'>$ext</b>","Ошибка запуска","bounce");
		}
	}
$d=dir($dir);
chdir($d->path);
$warfile=array(".htaccess");
$pathmain=$d->path;
if ($pathmain=='../../../'){
	$pathmain=realpath($entry);
}
$pathmain= substr($pathmain,strpos($pathmain,'os')+strlen('os'));
?>
<div style="cursor:default; width:30px;" onmouseover="document.getElementById('filemenu<?echo $appid;?>').style.display='block';" onmouseout="document.getElementById('filemenu<?echo $appid;?>').style.display='none';">
	<b>Файл</b>
	<div id="filemenu<?echo $appid;?>" style="display:none; cursor:default; position:absolute; z-index:9000; background:#fff; width:auto;">
<ul id="mmenu<?echo $appid;?>" >
	<li><div <?echo 'id="'.$dir.'/" class="loadthis" onClick="load'.$appid.'(this);" ';?> >Открыть</div></li>
	<li><div <?echo 'onClick="mkdirshow'.$appid.'();" ';?> >Создать папку</div></li>
	<li><div <?echo 'id="'.$dir.'/" class="mklink" onClick="link'.$appid.'(this);" ';?> >Создать ярлык</div></li>
	<li><div <?echo 'class="delete" onClick="deletes'.$appid.'(this);" ';?>>Отправить в корзину</div></li>
	<li><div <?echo 'class="deleteforever" onClick="deleteforever'.$appid.'(this);" ';?>>Удалить</div></li>
	<li><div <? echo 'id="'.$dir.'/" onClick="loadshow'.$appid.'(this);"';?>>Загрузить файл</div></li>
</ul>
</div>
</div>
<div id="mkdirdiv<?echo $appid;?>" style="width:43%; display:none; z-index:10; height:120px; padding:10px; background-color:#eaeaea; border: 1px solid #282828; position:absolute; margin-top:25%; text-align:center; overflow:hidden; left:25%;">
<label for="mkdirinput<?echo $appid;?>">
	Введите название
	<input id="mkdirvalue<?echo $appid;?>" style="font-size:20px; margin-bottom:10px;" name="mkdirinput<?echo $appid;?>" type="text" value="">
</label>
<span onclick="document.getElementById('mkdirdiv<?echo $appid;?>').style.display='none';" style="width:70px;" class="ui-button ui-widget ui-corner-all">
	Отмена
</span>
<span style="width:70px;" onClick="mkdirbtn<?echo $appid;?>();" class="ui-button ui-widget ui-corner-all">
	ОК
</span>
</div>
<?
echo '<input style="width:96%; font-size:17px; margin-left:10px;" type="search" value="os'.$pathmain.'"></input>';
while (false !== ($entry=$d->read())) {
	$path	=	$d->path;
	$name	=	$entry;
	if ($entry	==	'..'){
		$name	=	'&#9668назад';
		$extension='';
		$color	=	'#80abc6';
		$type	=	$folder.'/assets/folderico.png';
		$datecreate	=	'';
	}else{
		$color	=	'#ffee00';
		$extension	=	'';
		$type	=	$folder.'/assets/folderico.png';

		try {
			$fo->size_check(realpath(realpath($entry)));
			$fo->format($size);
			$format = '<br> Размер: '.$format;
		} catch (Exception $e) {
			echo $e->getMessage($e);
		}

		$datecreate = 'Дата: '.date('d.m.y H:i:s', filectime(realpath($entry))).$format;
	}
	if(is_file(realpath($entry))){
		$color='#b5b5b5';
		if($name	==	'main.php'){
			$type	=	$pathmain.'/app.png';
			$extension	=	"";
		}else{
			$extension	=	stristr($name, '.');
			$extension	=	str_replace('.','',$extension);
			$type	=	$folder.'/assets/fileico.png';
			if($extension	==	'png'  || $extension	==	'jpg' || $extension	==	'bmp' || $extension	==	'gif'){
				$color='#fff';
				$type	=	$pathmain.'/'.$entry;
				$extension	=	"";
			}
		}
		$fo->format(filesize(realpath($entry)));
		$datecreate = 'Дата: '.date('d.m.y H:i:s', filectime(realpath($entry))).'<br> Размер: '.$format;
	}

	$wardir = $_SERVER['DOCUMENT_ROOT'];
	$wardir = stristr($wardir, 'public_html');
	$wardir	= str_replace('public_html/','',$wardir);

	if ($entry!='.' && !in_array($entry,$warfile) && realpath($entry).'/'.$wardir!=$_SERVER['DOCUMENT_ROOT']){
		$name2="'".md5($name)."'";
		$name3="'".realpath($entry)."'";
		$name4="'".$type."'";
		$name5="'".$name."'";
	echo('<div id="'.realpath($entry).'" class="'.md5($name).' select ui-button ui-widget ui-corner-all" onClick="select'.$appid.'('.$name2.','.$name3.','.$name4.','.$name5.');" on'.$click.'="load'.$appid.'(this);"  style="cursor:default; height:128px;margin:5px;text-align:center;width:128px;position:relative;display:block;text-overflow:ellipsis;overflow:hidden;float:left;" title="'.$name.'"><div style="cursor:default; width:80px; height:80px; background-image: url('.$type.'); background-size:cover; -webkit-user-select:none; user-select:none; padding:5px; background-color:'.$color.'; margin:auto; color:#d05858; font-size:25px;">
	'.$extension.'</div><div style="text-overflow: ellipsis;overflow: hidden;font-size: 15px;">'.$name.'<div style="font-size:10px; padding:5px; color:#688ad8;">'.$datecreate.'</div></div></div>');
}
}
$dir->close;
?>
<div id="upload">
</div>

<div style="background-color:#e2e2e2; width:100%; position:absolute; top:100%; word-wrap:break-word;">
<?
$fo->size_check(dirname(dirname(dirname(__DIR__))));
$explorerbd = new readbd;
$explorerbd->readglobal2("hdd","forestusers","login",$_SESSION["loginuser"]);
$getdata2=$getdata*1000000;
$getdata=$getdata*1000000-$size;
$fo->format($getdata2);
$format2=$format;
$fo->format($getdata);
echo 'Свободно: '.$format .' из '.$format2 ;
?>
</div>
</div>
<script>
function load<?echo $appid;?>(el){
	$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?dir="+el.id+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")
};
function link<?echo $appid;?>(el2){
	$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?linkdir="+el2.id+"&ico="+el2.getAttribute('ico')+"&linkname="+el2.getAttribute('link')+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&dir='.realpath($entry).'&destination='.$folder;?>")
};
function select<?echo $appid;?>(folder,folder2,folder3,folder4){
	$(".select").css('background-color','transparent');
	$('.'+folder).css('background-color','#b5b5b5');
	$(".delete").attr("id",folder2);
	$(".deleteforever").attr("id",folder2);
	$(".loadthis").attr("id",folder2);
	$(".mklink").attr("id",folder2);
	$(".mklink").attr("ico",folder3);
	$(".mklink").attr("link",folder4);
};
function deletes<?echo $appid;?>(del){
	$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?del="+del.id+"&id=<?echo rand(0,10000).'&dir='.realpath($entry).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")
};
function deleteforever<?echo $appid;?>(delf){
	$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?delf="+delf.id+"&id=<?echo rand(0,10000).'&dir='.realpath($entry).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")
};
function loadshow<?echo $appid;?>(divs){
	$("#upload").load("<?echo $folder;?>/uploadwindow.php?where="+divs.id+"&id=<?echo rand(0,10000).'&appname='.$appname.'&destination='.$folder.'&mobile='.$click;?>")
};
function mkdirshow<?echo $appid;?>(){
	$("#mkdirdiv<?echo $appid;?>").css('display','block')
};
function mkdirbtn<?echo $appid;?>(){
	$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?makedir="+$("#mkdirvalue<?echo $appid;?>").val()+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&dir='.realpath($entry).'&destination='.$folder;?>")
};
$(function(){
	$("#mmenu<?echo $appid;?>").menu();
});
</script>
<style>.ui-menu{width: 150px;}</style>
<?
unset($appid);
?>
