<?
if($_GET['getinfo'] == 'true'){
	include '../../core/library/etc/appinfo.php';
	$appinfo = new AppInfo;
	$appinfo->setInfo('Explorer', '1.0', 'Forest Media', 'Проводник');
}
$appname=$_GET['appname'];
$appid=$_GET['appid'];
?>
<div id="<?echo $appname.$appid?>" style="background-color:#f2f2f2; height:500px; max-width:100%; width:800px; border-radius:0px 0px 5px 5px; overflow:auto;">
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

$dir = preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))',$_GET['defaultloader']);
if(empty($dir)){
	$dir = preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))',$_GET['dir']);	
}


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

if (isset($_GET['makefile'])){
	if(!is_file($dir.'/'.$_GET['makefile']))
	{
		$defaultExt = '';
		preg_match('/\.[^\.]+$/i',$_GET['makefile'],$ext);
		if($ext[0] == ''){
			$defaultExt = '.txt';
		}file_put_contents($dir.'/'.$_GET['makefile'].$defaultExt,'');
	}else{
		$dialogexplorer->newnotification($appname,$appname,$explorer_lang['mfile_msg_1']);
	}
}
// make new dir
if (isset($_GET['makedir'])){
	if(!is_dir($dir.'/'.$_GET['makedir']))
	{
		if(!mkdir($dir.'/'.$_GET['makedir'],0755)){
			$dialogexplorer->newnotification($appname,$appname,$explorer_lang['msg_1']." ".$_GET['makedir'].$explorer_lang['msg_2']);
		}
	}else{
		$dialogexplorer->newnotification($appname,$appname,$explorer_lang['msg_3']);
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
	if(is_file($deleteforever)){
		if(!preg_match('/os.php/',$deleteforever) && !preg_match('/login.php/',$deleteforever) && !preg_match('/makeprocess.php/',$deleteforever)){
			unlink($deleteforever);
		}else{
			throw new InvalidArgumentException("can't delete system file: $dirPath");
		}
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
	if($linkname == 'main.php'){
		$mainfile	=	str_replace('.php','',$linkname);
		$destination = str_replace($linkname,'',$link);
		$link = '';
		$param = '';
		$file = stristr($destination, 'apps/');
		$file = str_replace(array('apps/','/main.php', '/'),'',$file);
  	$info = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/system/apps/'.$file.'/main.php?getinfo=true&h='.md5(date('dmyhis')));
		$arrayInfo = json_decode($info);
		if($_SESSION['locale'] == 'en'){
			$newname	=	$arrayInfo->{'name'};
			$puplicname	=	$arrayInfo->{'name'};
		}else{
			$newname	=	$arrayInfo->{'secondname'};
			$puplicname	=	$arrayInfo->{'secondname'};
		}
		if(empty($newname) || empty($puplicname)){
			$newname = $file;
			$puplicname = $file;
		}
		$ico = stristr($ico,'?',true);
	}else{
		if(is_file($link)){
			$ext =	pathinfo($link);
			$ext = mb_strtolower($ext['extension']);
			if($ext == 'php'){
				$mainfile	=	str_replace('.php','',$linkname);
				$newname = $linkname;
				$puplicname = $linkname;
				$destination = str_replace($linkname,'',$link);
			}else{
				$ini_array = parse_ini_file("../../core/extconfiguration.foc");
				$param = $ini_array[$ext.'_key'];
				$mainfile	=	'main';
				$destination = str_replace('main.php','',$ini_array[$ext]);
				$link = str_replace($_SERVER['DOCUMENT_ROOT'],'',$link);
				$puplicname = $linkname;
				$newname = str_replace(array('system','apps','/'),'',$destination);
			}
		}else{
			$mainfile	=	'main';
			$param='dir';
			$destination="system/apps/Explorer/";
			$puplicname=$linkname;
			$newname='Explorer';
		}
	}
	$file = '../../users/'.$_SESSION["loginuser"].'/desktop/'.$puplicname.'_'.uniqid().'.link';
	$faction->makelink($file,$destination,$mainfile,$param,$link,$newname,$puplicname,$ico);
}

if (empty($dir)){
	$dir='../../../';
}
if(!is_dir($dir)){
	$ext=pathinfo($dir);
	$ext=mb_strtolower($ext['extension']);
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
<div style="position:absolute; width:100%; z-index:1; background:#f2f2f2; border:1px solid #d4d4d4; box-shadow: 0 1px 2px rgba(0,0,0,0.065);">

<div class="menucontainer" style="display: flex;">
<div class="ui-forest-menu-button" onmouseover="$('#filemenu<?echo $appid?>').css('display','block')" onmouseout="$('#filemenu<?echo $appid?>').css('display','none')">
	<span><?echo $explorer_lang['menu_file_label']?></span>
	<div id="filemenu<?echo $appid?>" style="display:none; cursor:default; position:absolute; z-index:1; background:#fff; width:auto; top:31px; left:4px;">
<ul id="mmenu<?echo $appid?>" >
	<li><div <?echo 'id="'.$dir.'/" class="loadthis" onClick="load'.$appid.'(this);" ';?> ><?echo $explorer_lang['menu_open_label']?></div></li>
	<li>
		<div <?echo 'id="'.$dir.'/" class="loadas" ';?> ><?echo $explorer_lang['menu_openas_label']?></div>
		<ul style="background:#fff;">
			<?
			foreach (glob($_SERVER['DOCUMENT_ROOT']."/system/apps/*/main.php") as $filenames)
			{
				$get_name = preg_match('/apps.*?\/(.*?)\/main.php/',$filenames, $app_name);
			  $_app_name = $app_name[1];
			  $app_name = str_replace('_', ' ', $_app_name);
				echo '<li><div onClick="makeprocess(\''.$_SERVER['DOCUMENT_ROOT'].'/system/apps/'.$_app_name.'/main.php\',$(\'.loadas\').attr(\'id\'),\'defaultloader\',\''.$_app_name.'\');">'.$app_name.'</div></li>';
			}
			?>
		</ul>
	</li>
	<li><div <?echo 'class="loadthis" onClick="mkfileshow'.$appid.'();" ';?> ><?echo $explorer_lang['menu_newfile_label']?></div></li>
	<li><div <? echo 'id="'.$dir.'/" class="loadthis" onClick="getproperty'.$appid.'(this);"';?>><?echo $explorer_lang['menu_rename_label']?></div></li>
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

<div class="ui-forest-menu-button" onmouseover="$('#editmenu_<?echo $appid?>').css('display','block')" onmouseout="$('#editmenu_<?echo $appid?>').css('display','none')">
	<span><?echo $explorer_lang['menu_edit_label']?></span>
	<div id="editmenu_<?echo $appid?>" style="display:none; cursor:default; position:absolute; z-index:1; background:#fff; width:auto; top:31px; left:68px;">
<ul id="editmenu<?echo $appid?>" >
	<li><div <?echo 'id="" class="loadthis" onClick="copy'.$appid.'(this.id);" ';?> ><?echo $explorer_lang['menu_copy_label']?></div></li>
	<li class="pastebutton"><div <?echo 'id="" class="loadthis" onClick="paste'.$appid.'(this.id);" ';?> ><?echo $explorer_lang['menu_paste_label']?></div></li>
	<li><div <?echo 'id="" class="loadthis" onClick="cut'.$appid.'(this.id);" ';?> ><?echo $explorer_lang['menu_cut_label']?></div></li>
</ul>
</div>
</div>
</div>

<div style="margin-top:7px; border-top:1px solid #d4d4d4; padding-top:7px;">
<div class="ui-forest-blink" style="padding:4px; background:#4d94ef; margin:0px 10px; border-radius:10px; color:#2b5182; float:left; width:20px;" id="<?echo $_SERVER['DOCUMENT_ROOT'].dirname($pathmain)?>" onclick="load<?echo $appid?>(this)">
	&#9668
</div>
<input style="-webkit-appearance:none; border:1px solid #ccc; width:80%; font-size:17px; margin: 0 5px 10px;" type="search" value="os<?echo $pathmain?>"></input>
</div>
</div>
<div id="mkdirdiv<?echo $appid?>" style="z-index:1; position:fixed; display:none; top:25%; left:25%; background-color:#ededed; border:1px solid #797979; padding:20px; border-radius:6px; box-shadow:1px 1px 5px #000; width:min-content; text-align:center;">
<label for="mkdirinput<?echo $appid?>">
	<?echo $explorer_lang['mdir_label']?>
	<input id="mkdirvalue<?echo $appid?>" style="font-size:20px; margin-bottom:10px;" name="mkdirinput<?echo $appid?>" type="text" value="">
</label>
<span onclick="$('#mkdirdiv<?echo $appid?>').css('display','none');" style="width:70px;" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mdir_cancelbtn']?>
</span>
<span style="width:70px;" onClick="mkdirbtn<?echo $appid?>();" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mdir_okbtn']?>
</span>
</div>

<div id="mkfilediv<?echo $appid?>" style="z-index:1; position:fixed; display:none; top:25%; left:25%; background-color:#ededed; border:1px solid #797979; padding:20px; border-radius:6px; box-shadow:1px 1px 5px #000; width:min-content; text-align:center;">
<label for="mkfileinput<?echo $appid?>">
	<?echo $explorer_lang['mfile_label']?>
	<input id="mkfilevalue<?echo $appid?>" style="font-size:20px; margin-bottom:10px;" name="mkfileinput<?echo $appid?>" type="text" value="">
</label>
<span onclick="$('#mkfilediv<?echo $appid?>').css('display','none');" style="width:70px;" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mfile_cancelbtn']?>
</span>
<span style="width:70px;" onClick="mkfilebtn<?echo $appid?>();" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mfile_okbtn']?>
</span>
</div>

<div style="margin: 92px 0;">
<?
while (false !== ($entry=$d->read())) {
	$path	=	$d->path;
	$name	=	$entry;
	if ($entry	!=	'..'){
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
		<div id="erasetrash<?echo $appid?>" onClick="erasetrash<?echo $appid?>();" class="ui-forest-button ui-forest-cancel" style="margin:5px; padding:64px 10px; float:left; display:none; height:14px;">
			<b><?echo $explorer_lang['trash_label']?></b>
		</div>
		<script>
		$('#erasetrash<?echo $appid?>').css('display','block');
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
			$extension	=	mb_strtolower(str_replace('.','',$extension));
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

	if ($entry!='.' && $entry!='..' && !in_array($entry,$warfile) && realpath($entry).'/'.$wardir!=$_SERVER['DOCUMENT_ROOT']){
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

	echo('<div id="'.realpath($entry).'" class="'.md5($name).' select ui-button ui-widget ui-corner-all explorer-object" onClick="'.$select.'" on'.$click.'="'.$load.'"  style="cursor:default; height:128px;	margin:5px;	text-align:center;	width:128px;	position:relative;	display:block;	text-overflow:ellipsis;	overflow:hidden;	float:left; transition:all 0.05s ease-out;" title="'.$name.'"><div style="cursor:default; width:80px; height:80px; background-image: url('.$type.'); background-size:cover; -webkit-user-select:none; user-select:none; padding:5px; background-color:'.$color.'; margin:auto; color:#d05858; font-size:25px;">
	'.$extension.'</div><div style="text-overflow: ellipsis;overflow: hidden;font-size: 15px;"><span style="color:'.$n_color.'; white-space:nowrap;">'.$name.'</span><div style="font-size:10px; padding:5px; color:#688ad8;">'.$datecreate.'</div></div></div>');
}
}
$dir->close;
?>
</div>
<div id="upload<?echo $appid?>" style="z-index:1; position:fixed; display:none; top:25%; left:25%; background-color:#ededed; border:1px solid #797979; padding:20px; border-radius:6px; box-shadow:1px 1px 5px #000;">
</div>

<div style="padding:0 10px; background-color:#f2f2f2; width:97%; top:97%; word-wrap:break-word; font-size:10px; float:right; position:absolute; text-align:right;">
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
function load<?echo $appid?>(el){
	$("#<?echo $appid?>").load("<?echo $folder;?>main.php?dir="+el.id+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder?>")
};

function loadshow<?echo $appid?>(divs){
	$("#upload<?echo $appid?>").load("<?echo $folder;?>/uploadwindow.php?where="+divs.id+"&id=<?echo rand(0,10000).'&appname='.$appname.'&appid='.$appid.'&destination='.$folder.'&mobile='.$click;?>")
	$("#upload<?echo $appid?>").css('display', 'block');
};
function link<?echo $appid?>(el2){
	$("#<?echo $appid?>").load("<?echo $folder;?>main.php?linkdir="+el2.id+"&ico="+el2.getAttribute('ico')+"&linkname="+el2.getAttribute('link')+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&dir='.realpath($entry).'&destination='.$folder;?>")
};
function getproperty<?echo $appid?>(obj){
	makeprocess('<?echo $folder?>property.php',obj.id,'object','<?echo $explorer_lang['menu_property_label']?>');
};
function select<?echo $appid?>(folder,folder2,folder3,folder4){
	$(".select").css('background-color','transparent');
	$('.'+folder).css('background-color','#d4d4d4');
	$(".loadthis").attr("id",folder2);
	$(".loadas").attr("id",folder2);
	$(".mklink").attr("id",folder2);
	$(".mklink").attr("ico",folder3);
	$(".mklink").attr("link",folder4);
};

function erasetrash<?echo $appid?>(){
	$("#<?echo $appid?>").load("<?echo $folder;?>/main.php?dir=<?echo realpath($entry)?>&erasestatus=true&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder;?>")
};

function mkdirshow<?echo $appid?>(){
	$("#mkdirdiv<?echo $appid?>").css('display','block')
};

function mkfileshow<?echo $appid?>(){
	$("#mkfilediv<?echo $appid?>").css('display','block')
};

function mkdirbtn<?echo $appid?>(){
	$("#<?echo $appid?>").load("<?echo $folder;?>/main.php?makedir="+$("#mkdirvalue<?echo $appid?>").val()+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&dir='.realpath($entry).'&destination='.$folder;?>")
};

function mkfilebtn<?echo $appid?>(){
	$("#<?echo $appid?>").load("<?echo $folder;?>/main.php?makefile="+$("#mkfilevalue<?echo $appid?>").val()+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&dir='.realpath($entry).'&destination='.$folder;?>")
};

function newload<?echo $appid?>(key,value){
$("#<?echo $appid?>").load("<?echo $folder;?>/main.php?"+key+"="+value+"&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&dir='.realpath($entry).'&destination='.$folder;?>")
};

function reload<? echo $appid?>(){
	$("#<?echo $appid?>").load("<?echo $folder;?>main.php?dir=<?echo realpath($entry)?>&id=<?echo rand(0,10000).'&appid='.$appid.'&mobile='.$click.'&appname='.$appname.'&destination='.$folder?>");
}

function checkbutton(){
	if(localStorage.getItem('copy') == null && localStorage.getItem('cut') == null){
		$('.pastebutton').css({
			'pointer-events' : 'none',
			'opacity' : '0.6'
		});
	}else{
		$('.pastebutton').css({
			'pointer-events' : 'all',
			'opacity' : '1'
		});
	}
}

function copy<?echo $appid?>(file){
	localStorage.setItem('copy', file);
	checkbutton();
};

function paste<?echo $appid?>(file){
	var getFile = localStorage.getItem('copy');
	var action = '';
	if(getFile != null){
		action = 'copy';
	}else{
		getFile = localStorage.getItem('cut');
		localStorage.removeItem('cut');
		action = 'cut';
	}

	$.ajax({
		type: "POST",
		url: "system/core/functions/filesystem",
		data: {
			 f:getFile,
			 n:"<?echo realpath($entry).'/'?>",
			 a:action
		}
	}).done(function(o) {
		reload<?echo $appid?>();
});
	checkbutton();
};

function cut<?echo $appid?>(file){
	localStorage.removeItem('copy');
	localStorage.setItem('cut', file);
	checkbutton();
};

$(function(){
	$("#editmenu<?echo $appid?>").menu();
	$("#mmenu<?echo $appid?>").menu();
	$("#makeprocess").remove();
});
UpdateWindow("<?echo $appid?>","<?echo $appname?>");
checkbutton();
</script>
<style>.ui-menu{width: 150px;}</style>
<?
unset($appid);
?>
