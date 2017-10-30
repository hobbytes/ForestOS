<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; height:500px; max-width:100%; width:800px; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*Explorer*/
//Подключаем библиотеки
include '../../core/library/filesystem.php';
include '../../core/library/bd.php';
include '../../core/library/gui.php';
include '../../core/library/etc/security.php';
//Инициализируем переменные
$fo = new filecalc;
$faction = new fileaction;
$security	=	new security;
$dir = $_GET['dir'];
$del = $_GET['del'];
$deleteforever = $_GET['delf'];
$link	=	$_GET['linkdir'];
$linkname	=	$_GET['linkname'];
$ico	=	$_GET['ico'];
$click	=	$_GET['mobile'];
$folder	=	$_GET['destination'];
$erasestatus	=	$_GET['erasestatus'];
$zipfile = $_GET['zipfile'];
$dialogexplorer = new gui;
//Запускаем сессию
session_start();
$security->appprepare();
//Загружаем файл локализации
$cl = $_SESSION['locale'];
$explorer_lang  = parse_ini_file('assets/lang/'.$cl.'.lang');
if($erasestatus){
$faction->deleteDir($dir);
mkdir($dir);
}

if (isset($_GET['makedir'])){
	if(!is_dir($dir.'/'.$_GET['makedir']))
	{
		if(mkdir($dir.'/'.$_GET['makedir'],0755)){
			echo $explorer_lang['msg_1']." ".$_GET['makedir'].$explorer_lang['msg_2'];
		}else{
			echo $explorer_lang['msg_1']." ".$_GET['makedir'].$explorer_lang['msg_3'];
		}
	}else{
		echo $explorer_lang['msg_4'];
	}
}
//обрабатываем кнопки удаления и перемещения в корзину
if(!empty($del)){
	$faction->rmdir_recursive($del);
}
if(!empty($deleteforever)){
	if(is_dir($deleteforever)){
		$faction->deleteDir($deleteforever);
	}
	if(is_file($deleteforever) && !preg_match('/os.php/',$deleteforever) && !preg_match('/login.php/',$deleteforever) && !preg_match('/makeprocess.php/',$deleteforever)){
		unlink($deleteforever);
	}else{
		throw new InvalidArgumentException("can't delete system file: $dirPath");
	}
}
//Логика
/*-Упаковка объектов-*/
if(!empty($zipfile)){
include '../../core/library/zip.php';
if(is_dir($zipfile)){
	$zip = new zip;
	$zip->toZip($zipfile,dirname($zipfile).'/'.basename($zipfile).'.zip');
}else{
	$zip = new ZipArchive;
	$info = pathinfo($zipfile);
	$zip->open(dirname($zipfile).'/'.basename($zipfile,'.'.$info['extension']).'.zip', ZIPARCHIVE::CREATE);
	$zip->addFile($zipfile,basename($zipfile));
	$zip->close();
}
}
/*-Создание ярлыка-*/
if(!empty($link)){
	if($linkname=='main.php'){
		$mainfile	=	str_replace('.php','',$linkname);
		$destination=str_replace($linkname,'',$link);
		$link='';
		$param='';
		$newname=stristr($destination, 'apps/');
		$newname=str_replace(array('apps/','/main.php', '/'),'',$newname);
		$puplicname=$newname;
		$ico = stristr($ico,'?',true);
	}else{
		$mainfile	=	'main';
		$param='dir';
		$destination="system/apps/Explorer/";
		$puplicname=$linkname;
		$newname='Explorer';
	}
	$file = '../../users/'.$_SESSION["loginuser"].'/desktop/'.$puplicname.'_'.uniqid().'.link';
	$faction->makelink($file,$destination,$mainfile,$param,$link,$newname,$puplicname,$ico);
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
		$param	= str_replace($_SERVER['DOCUMENT_ROOT'],'',$dir);
		$keys=$ini_array[$ext.'_key'];
		$dir=dirname($dir);
	}
	if (!empty($dest)){
		$name_launch = basename($param);
		?>
		<div id="makeprocess">
			<script>makeprocess('<?echo $dest?>','<?echo $param;?>','<?echo $keys;?>','<?echo $name_launch?>');</script>
		</div>
		<?}else{
			$dialogexplorer->dialog($explorer_lang['error_open']."*.$ext</b>",$explorer_lang['error_label'],"bounce");
		}
	}
$d=dir($dir);
chdir($d->path);
$warfile=array(".htaccess");
$pathmain=$d->path;
if ($pathmain=='../../../'){
	$pathmain=realpath($entry);
}
$pathmain = str_replace($_SERVER['DOCUMENT_ROOT'],'',$pathmain);
?>
<div >
<div style="cursor:default; float:left; padding:5px 10px;" onmouseover="document.getElementById('filemenu<?echo $appid;?>').style.display='block';" onmouseout="document.getElementById('filemenu<?echo $appid;?>').style.display='none';">
	<b><?echo $explorer_lang['menu_file_label']?></b>
	<div id="filemenu<?echo $appid;?>" style="display:none; cursor:default; position:absolute; z-index:9000; background:#fff; width:auto;">
<ul id="mmenu<?echo $appid;?>" >
	<li><div <?echo 'id="'.$dir.'/" class="loadthis" onClick="load'.$appid.'(this);" ';?> ><?echo $explorer_lang['menu_open_label']?></div></li>
	<li><div <?echo 'onClick="mkdirshow'.$appid.'();" ';?> ><?echo $explorer_lang['menu_md_label']?></div></li>
	<li><div <?echo 'id="'.$dir.'/" class="mklink" onClick="link'.$appid.'(this);" ';?> ><?echo $explorer_lang['menu_ml_label']?></div></li>
	<li><div <?echo 'class="loadthis" onClick="newload'.$appid.'('."'del'".',this.id)" ';?>><?echo $explorer_lang['menu_trash_label']?></div></li>
	<li><div <?echo 'class="loadthis" onClick="newload'.$appid.'('."'delf'".',this.id)" ';?>><?echo $explorer_lang['menu_delete_label']?></div></li>
	<li><div <? echo 'id="'.$dir.'/" onClick="loadshow'.$appid.'(this)"';?>><?echo $explorer_lang['menu_loadfile_label']?></div></li>
	<li><div <? echo 'class="loadthis" onClick="newload'.$appid.'('."'zipfile'".',this.id)"';?>><?echo $explorer_lang['menu_zip_label']?></div></li>
	<li><div <? echo 'id="'.$dir.'/" class="loadthis" onClick="getproperty'.$appid.'(this);"';?>><?echo $explorer_lang['menu_property_label']?></div></li>
</ul>
</div>
</div>
<div id="erasetrash<?echo $appid;?>" onClick="erasetrash<?echo $appid;?>();" class="ui-forest-button ui-forest-cancel" style="margin:3px auto; padding:5px 10px; float:left; display:none;">
	<b><?echo $explorer_lang['trash_label']?></b>
</div>
</div>
<div id="mkdirdiv<?echo $appid;?>" style="width:43%; display:none; z-index:10; height:120px; padding:10px; background-color:#eaeaea; border: 1px solid #282828; position:absolute; margin-top:25%; text-align:center; overflow:hidden; left:25%;">
<label for="mkdirinput<?echo $appid;?>">
	<?echo $explorer_lang['mdir_label']?>
	<input id="mkdirvalue<?echo $appid;?>" style="font-size:20px; margin-bottom:10px;" name="mkdirinput<?echo $appid;?>" type="text" value="">
</label>
<span onclick="document.getElementById('mkdirdiv<?echo $appid;?>').style.display='none';" style="width:70px;" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mdir_cancelbtn']?>
</span>
<span style="width:70px;" onClick="mkdirbtn<?echo $appid;?>();" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mdir_okbtn']?>
</span>
</div>
<?
echo '<input style="width:96%; font-size:17px; margin-left:10px;" type="search" value="os'.$pathmain.'"></input>';
while (false !== ($entry=$d->read())) {
	$path	=	$d->path;
	$name	=	$entry;
	if ($entry	==	'..'){
		$name	=	'&#9668 '.$explorer_lang['back_label'];
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
			if (empty($size)){
				$format	= '0 Bytes';
			}
			$format = '<br> '.$explorer_lang['size'].': '.$format;
		} catch (Exception $e) {
			echo $e->getMessage($e);
		}

		$datecreate = $explorer_lang['date'].': '.date('d.m.y H:i:s', filectime(realpath($entry))).$format;
	}
	if(eregi($_SESSION["loginuser"].'/trash',$pathmain)){
		?>
		<script>
		$('#erasetrash<?echo $appid;?>').css('display','block');
		</script>
		<?
	}
	if(is_file(realpath($entry))){
		$object	=	$dialogexplorer;
		$color='rgba(0,0,0,0)';
		if($name	==	'main.php'){
			if(file_exists('app.png')){
				$hashfileprefix	= $faction->filehash('app.png','false');
				$type	=	$pathmain.'/'.$hashfileprefix;
			}else{
				$type	=	'system/core/design/images/app.png';
			}
			$extension	=	"";
		}else{
			$extension	=	stristr($name, '.');
			$extension	=	str_replace('.','',$extension);
			$type	=	$folder.'/assets/fileico.png';
			if($extension	==	'png'  || $extension	==	'jpg' || $extension	==	'jpeg' || $extension	==	'bmp' || $extension	==	'gif'){
				$color='transparent';
				$hashfileprefix	= $faction->filehash($entry,'false');
				$type	=	$pathmain.'/'.$hashfileprefix;
				$extension	=	"";
			}
		}
		$fo->format(filesize(realpath($entry)));
		$datecreate = $explorer_lang['date'].': '.date('d.m.y H:i:s', filectime(realpath($entry))).'<br> '.$explorer_lang['size'].': '.$format;
	}

	$wardir = $_SERVER['DOCUMENT_ROOT'];
	$wardir = stristr($wardir, 'public_html');
	$wardir	= str_replace('public_html/','',$wardir);

	if ($entry!='.' && !in_array($entry,$warfile) && realpath($entry).'/'.$wardir!=$_SERVER['DOCUMENT_ROOT']){
		$name2="'".md5($name)."'";
		$name3="'".realpath($entry)."'";
		$name4="'".$type."'";
		$name5="'".$name."'";
		$select	=	'select'.$appid.'('.$name2.','.$name3.','.$name4.','.$name5.');';
		$load = 'load'.$appid.'(this);';
		$n_color	=	'#000';
		if(eregi('system/users/',realpath($entry)) || eregi('system/core',realpath($entry))){
			if($_SESSION['superuser'] != $_SESSION['loginuser'] && !eregi('system/users/'.$_SESSION['loginuser'],realpath($entry)) || $_SESSION['superuser'] != $_SESSION['loginuser'] && eregi('system/core',realpath($entry))){
			$select	=	'';
			$load = '';
			$n_color	=	'#e63030';
		}
	}

	echo('<div id="'.realpath($entry).'" class="'.md5($name).' select ui-button ui-widget ui-corner-all explorer-object" onClick="'.$select.'" on'.$click.'="'.$load.'"  style="cursor:default; height:128px;margin:5px;text-align:center;width:128px;position:relative;display:block;text-overflow:ellipsis;overflow:hidden;float:left;" title="'.$name.'"><div style="cursor:default; width:80px; height:80px; background-image: url('.$type.'); background-size:cover; -webkit-user-select:none; user-select:none; padding:5px; background-color:'.$color.'; margin:auto; color:#d05858; font-size:25px;">
	'.$extension.'</div><div style="text-overflow: ellipsis;overflow: hidden;font-size: 15px;"><span style="color:'.$n_color.';">'.$name.'</span><div style="font-size:10px; padding:5px; color:#688ad8;">'.$datecreate.'</div></div></div>');
}
}
$dir->close;
?>
<div id="upload<?echo $appid;?>" style="position:fixed; display:none; width:350px; top:25%; left:25%; background-color:#f9f9f9; border:5px solid #505050; padding:20px; border-radius:10px;">
</div>

<div style="padding:0 10px; background-color:#f2f2f2; width:97%; position:absolute; top:96%; word-wrap:break-word;">
<?
$fo->size_check(dirname(dirname(dirname(__DIR__))));
$explorerbd = new readbd;
$explorerbd->readglobal2("hdd","forestusers","login",$_SESSION["loginuser"]);
$getdata2=$getdata*1000000;
$getdata=$getdata*1000000-$size;
$fo->format($getdata2);
$format2=$format;
$fo->format($getdata);
echo $explorer_lang['free_label'].': '.$format .' '.$explorer_lang['free_label_2'].' '.$format2 ;
?>
</div>
</div>
<script>
function load<?echo $appid;?>(el){
	$("#<?echo $appid;?>").load("<?echo $folder;?>main.php?dir="+el.id+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder?>")
};function loadshow<?echo $appid;?>(divs){
	$("#upload<?echo $appid;?>").load("<?echo $folder;?>/uploadwindow.php?where="+divs.id+"&id=<?echo rand(0,10000).'&appname='.$appname.'&appid='.$appid.'&destination='.$folder.'&mobile='.$click;?>")
	$("#upload<?echo $appid;?>").css('display', 'block');
};
function link<?echo $appid;?>(el2){
	$("#<?echo $appid;?>").load("<?echo $folder;?>main.php?linkdir="+el2.id+"&ico="+el2.getAttribute('ico')+"&linkname="+el2.getAttribute('link')+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&dir='.realpath($entry).'&destination='.$folder;?>")
};
function getproperty<?echo $appid;?>(obj){
	makeprocess('<?echo $folder?>property.php',obj.id,'object','<?echo $explorer_lang['menu_property_label']?>');
};
function select<?echo $appid;?>(folder,folder2,folder3,folder4){
	$(".select").css('background-color','transparent');
	$('.'+folder).css('background-color','#b5b5b5');
	$(".loadthis").attr("id",folder2);
	$(".mklink").attr("id",folder2);
	$(".mklink").attr("ico",folder3);
	$(".mklink").attr("link",folder4);
};

function erasetrash<?echo $appid;?>(){
	$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?dir=<?echo realpath($entry)?>&erasestatus=true&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")
};

function mkdirshow<?echo $appid;?>(){
	$("#mkdirdiv<?echo $appid;?>").css('display','block')
};
function mkdirbtn<?echo $appid;?>(){
	$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?makedir="+$("#mkdirvalue<?echo $appid;?>").val()+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&dir='.realpath($entry).'&destination='.$folder;?>")
};
function newload<?echo $appid;?>(key,value){
$("#<?echo $appid;?>").load("<?echo $folder;?>/main.php?"+key+"="+value+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&dir='.realpath($entry).'&destination='.$folder;?>")
};
$(function(){
	$("#mmenu<?echo $appid;?>").menu();
	$("#makeprocess").remove();
});
</script>
<style>.ui-menu{width: 150px;}</style>
<?
unset($appid);
?>
