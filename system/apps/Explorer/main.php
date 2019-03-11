<?
/*Explorer*/

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;

/* App Info */
$AppContainer->AppNameInfo = 'Explorer';
$AppContainer->SecondNameInfo = 'Проводник';
$AppContainer->VersionInfo = '1.0.9';
$AppContainer->AuthorInfo = 'Forest Media';

/* Library List */
$AppContainer->LibraryArray = Array('filesystem','bd','gui');

/* Container Info */
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '530px';
$AppContainer->customStyle = 'padding-top:0px; max-width:100%;';
$AppContainer->isMobile = $_GET['mobile'];
$AppContainer->StartContainer();

//set var
$fo = new filecalc;
$faction = new fileaction;
$dialogexplorer = new gui;
$hashImage = md5($AppContainer->VersionInfo);

//callback data

$ExplorerMode = NULL;
$callback = NULL;

$ExplorerMode = $AppContainer->GetAnyRequest('explorermode');
$callback = $AppContainer->GetAnyRequest('callback');
$ShowOnly = $AppContainer->GetAnyRequest('showonly');

if(!empty($ShowOnly)){
	$_ShowOnly = $ShowOnly;
	$ShowOnly = explode(',', $ShowOnly);
}

//convert
function convert($string){
	$string = preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))',$string);
	return $string;
}



$dir = convert($AppContainer->GetAnyRequest('defaultloader'));

if(empty($dir)){
	$dir = convert($AppContainer->GetAnyRequest('dir'));
}

$del = $AppContainer->GetAnyRequest('del');
$deleteforever = $AppContainer->GetAnyRequest('delf');
$link	=	$AppContainer->GetAnyRequest('linkdir');
$linkname	=	$AppContainer->GetAnyRequest('linkname');
$ico	=	$AppContainer->GetAnyRequest('ico');
$isMobile	=	$AppContainer->GetAnyRequest('mobile');
$Folder	=	$AppContainer->GetAnyRequest('destination');
$erasestatus	=	$AppContainer->GetAnyRequest('erasestatus');
$zipfile = $AppContainer->GetAnyRequest('zipfile');
$zipFileUnpack = $AppContainer->GetAnyRequest('zipfileunpack');

//load lang
$cl = $_SESSION['locale'];
$explorer_lang  = parse_ini_file('assets/lang/'.$cl.'.lang');

//delete
if($erasestatus){
	$faction->deleteDir($dir);
	mkdir($dir);
}

//make file
if (isset($_GET['makefile'])){
	$newFile = convert(str_replace(' ', '_', $_GET['makefile']));
	if(!is_file($dir.'/'.$newFile))
	{
		$defaultExt = '';
		preg_match('/\.[^\.]+$/i',$newFile,$ext);
		if($ext[0] == ''){
			$defaultExt = '.txt';
		}
		file_put_contents($dir.'/'.$newFile.$defaultExt,'');
	}else{
		$dialogexplorer->newnotification($AppName,$AppName,$explorer_lang['mfile_msg_1']);
	}
}

// make new dir
if (isset($_GET['makedir'])){
	if(!is_dir($dir.'/'.$_GET['makedir']))
	{
		if(!mkdir($dir.'/'.convert(str_replace(' ', '_', $_GET['makedir'])),0755)){
			$dialogexplorer->newnotification($AppName, $AppName, $explorer_lang['msg_1']." ".$_GET['makedir'].$explorer_lang['msg_2']);
		}
	}else{
		$dialogexplorer->newnotification($AppName, $AppName, $explorer_lang['msg_3']);
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

/*-add to archive-*/
if(!empty($zipfile)){
	require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/zip.php';
	if(is_dir($zipfile)){
		$zip = new zip;
		$zip->toZip($zipfile, dirname($zipfile).'/'.basename($zipfile).'.zip');
	}else{
		$zip = new ZipArchive;
		$info = pathinfo($zipfile);
		$zip->open(dirname($zipfile).'/'.basename($zipfile,'.'.$info['extension']).'.zip', ZIPARCHIVE::CREATE);
		$zip->addFile($zipfile, basename($zipfile));
		$zip->close();
	}
}

/*-extract archive-*/
if(!empty($zipFileUnpack)){
	$zip = new ZipArchive;
	if($zip->open($zipFileUnpack) == TRUE){
		$zip->extractTo(dirname($zipFileUnpack));
		$zip->close();
	}
}

/*- Link Create -*/
if(!empty($link)){
	$ico = stristr($ico,'?',true);
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
			$param = 'dir';
			$destination = "system/apps/Explorer/";
			$puplicname = $linkname;
			$newname = 'Explorer';
		}
	}

	$file = '../../users/'.$_SESSION["loginuser"].'/desktop/'.$puplicname.'_'.uniqid().'.link';
	$faction->makelink($file,$destination,$mainfile,$param,$link,$newname,$puplicname,$ico);
}

if (empty($dir)){
	$dir = $_SERVER['DOCUMENT_ROOT'];
}

if(!is_dir($dir)){
	$ext = pathinfo($dir);
	$ext = mb_strtolower($ext['extension']);
	if($ext == 'php'){
		$file = basename($dir,'.php');
		$dest = $dir;
		$dir = dirname($dir);
		$param = '';
		$keys = '';
	}else{
		$ini_array = parse_ini_file("../../core/extconfiguration.foc");
		$dest = $ini_array[$ext];
		$param	= str_replace($_SERVER['DOCUMENT_ROOT'],'',$dir);
		$keys = $ini_array[$ext.'_key'];
		$dir = dirname($dir);
	}

	if (!empty($dest)){

		$_dest = str_replace($_SERVER['DOCUMENT_ROOT'], '', $dest);

		$info = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/'.$_dest.'?getinfo=true&h='.md5(date('dmyhis')));

	  $arrayInfo = json_decode($info);
	  if($_SESSION['locale'] == 'en'){
	    $name_launch = $arrayInfo->{'name'};
	  }else{
	    $name_launch = $arrayInfo->{'secondname'};
	  }

		$name_launch = str_replace(' ', '_', $name_launch);

		if(empty($name_launch)){
			$name_launch = 'Unknow_App';
		}

		?>
		<div id="makeprocess">
			<script>makeprocess('<?echo $dest?>','<?echo $param;?>','<?echo $keys;?>','<?echo $name_launch?>');</script>
		</div>
		<?}else{
			$dialogexplorer->dialog($explorer_lang['error_open']."*.$ext</b>",$explorer_lang['error_label'],"bounce");
		}
	}

$d = dir($dir);
chdir($d->path);

$warfile = array(".htaccess", "app.hash");

$pathmain = $d->path;

$prefix = 'os';

if ($pathmain == '../../../'){
	$pathmain = realpath($entry);
}

if($_SESSION['godmode'] == 'false'){
	$pathmain = str_replace($_SERVER['DOCUMENT_ROOT'], '', $pathmain);
	$back = $_SERVER['DOCUMENT_ROOT'].dirname($pathmain);
}

if($_SESSION['godmode'] == 'true'){
	$back = dirname($pathmain);
}

$pathmain = str_replace($_SERVER['DOCUMENT_ROOT'], '', $pathmain);

?>
<div style="position:absolute; width:100%; z-index:1; background:#f2f2f2; border:1px solid #d4d4d4; box-shadow: 0 1px 2px rgba(0,0,0,0.065);">

<div class="menucontainer" style="display: flex;">
<div class="ui-forest-menu-button" onmouseover="$('#filemenu<?echo $AppID?>').css('display','block')" onmouseout="$('#filemenu<?echo $AppID?>').css('display','none')">
	<span><?echo $explorer_lang['menu_file_label']?></span>
	<div id="filemenu<?echo $AppID?>" style="display:none; cursor:default; position:absolute; z-index:1; background:#fff; width:auto; top:31px; left:4px;">
<ul id="mmenu<?echo $AppID?>" >
	<li><div <?echo 'id="'.$dir.'/" class="loadthis'.$AppID.'" onClick="load'.$AppID.'(this);" ';?> ><?echo $explorer_lang['menu_open_label']?> <span style="font-size: 10px; color:#a2a2a2;">Shift+O</span> </div></li>
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
	<li><div <?echo 'class="loadthis'.$AppID.'" onClick="mkfileshow'.$AppID.'();" ';?> ><?echo $explorer_lang['menu_newfile_label']?>  <span style="font-size: 10px; color:#a2a2a2;">Shift+N</span>  </div></li>
	<li><div <? echo 'id="'.$dir.'/" class="loadthis'.$AppID.'" onClick="getproperty'.$AppID.'(this);"';?>><?echo $explorer_lang['menu_rename_label']?></div></li>
	<li><div <?echo 'onClick="mkdirshow'.$AppID.'();" ';?> ><?echo $explorer_lang['menu_md_label']?>  <span style="font-size: 10px; color:#a2a2a2;">Shift+F</span>  </div></li>
	<li><div <?echo 'id="'.$dir.'/" class="mklink" onClick="link'.$AppID.'(this);" ';?> ><?echo $explorer_lang['menu_ml_label']?></div></li>
	<li><div <?echo 'class="loadthis'.$AppID.' trashtrigger'.$AppID.'" messageTitle="'.$explorer_lang['mt_trash'].'" messageBody="'.$explorer_lang['mb_trash'].'" okButton="'.$explorer_lang['btn_trash_ok'].'" cancelButton="'.$explorer_lang['mfile_cancelbtn'].'" onClick="ExecuteFunctionRequest'.$AppID.'(this, \'newload'.$AppID.'\', array = [\'del\', this.id], true)" >'.$explorer_lang['menu_trash_label']; ?> <span style="font-size: 10px; color:#a2a2a2;">Ctrl+Del</span> </div></li>
	<li><div <?echo 'class="loadthis'.$AppID.' deletetrigger'.$AppID.'" messageTitle="'.$explorer_lang['mt_delete'].'" messageBody="'.$explorer_lang['mb_delete'].'" okButton="'.$explorer_lang['btn_delete_ok'].'" cancelButton="'.$explorer_lang['mfile_cancelbtn'].'" onClick="ExecuteFunctionRequest'.$AppID.'(this, \'newload'.$AppID.'\', array = [\'delf\', this.id], true)" >'.$explorer_lang['menu_delete_label']; ?> <span style="font-size: 10px; color:#a2a2a2;">Shift+Del</span> </div></li>
	<li><div <? echo 'id="'.$dir.'/" class="load-class'.$AppID.'" onClick="loadshow'.$AppID.'(this)"';?>><?echo $explorer_lang['menu_loadfile_label']?></div></li>
	<li><div <? echo 'class="loadthis'.$AppID.'" onClick="newload'.$AppID.'('."'zipfile'".',this.id)"';?>><?echo $explorer_lang['menu_zip_label']?></div></li>
	<li><div <? echo 'class="loadthis'.$AppID.'" onClick="newload'.$AppID.'('."'zipfileunpack'".',this.id)"';?>><?echo $explorer_lang['menu_zip_unpack']?></div></li>
	<li><div <? echo 'id="'.$dir.'/" class="loadthis'.$AppID.'" onClick="getproperty'.$AppID.'(this);"';?>><?echo $explorer_lang['menu_property_label']?>  <span style="font-size: 10px; color:#a2a2a2;">Shift+P</span>  </div></li>
</ul>
</div>
</div>

<div class="ui-forest-menu-button" onmouseover="$('#editmenu_<?echo $AppID?>').css('display','block')" onmouseout="$('#editmenu_<?echo $AppID?>').css('display','none')">
	<span><?echo $explorer_lang['menu_edit_label']?></span>
	<div id="editmenu_<?echo $AppID?>" style="display:none; cursor:default; position:absolute; z-index:1; background:#fff; width:auto; top:31px; left:68px;">
<ul id="editmenu<?echo $AppID?>" >
	<li><div <?echo 'id="" class="loadthis'.$AppID.'" onClick="copy'.$AppID.'(this.id);" ';?> ><?echo $explorer_lang['menu_copy_label']?> <span style="font-size: 10px; color:#a2a2a2;">Ctrl+C</span> </div></li>
	<li class="pastebutton"><div <?echo 'id="" class="loadthis'.$AppID.'" onClick="paste'.$AppID.'(this.id);" ';?> ><?echo $explorer_lang['menu_paste_label']?> <span style="font-size: 10px; color:#a2a2a2;">Ctrl+V</span> </div></li>
	<li><div <?echo 'id="" class="loadthis'.$AppID.'" onClick="cut'.$AppID.'(this.id);" ';?> ><?echo $explorer_lang['menu_cut_label']?> <span style="font-size: 10px; color:#a2a2a2;">Ctrl+X</span> </div></li>
</ul>
</div>
</div>
	<?

	//show select button if is mobile
	if($isMobile == 'true'){
		if(empty($_GET['select']) || $_GET['select'] == 'false'){
			echo '<div id="selectbutton-'.$AppID.'" onclick="selectButtonActive'.$AppID.'(true)" style="margin-top:2px;" class="ui-forest-button ui-forest-accept">'.$explorer_lang['selectButton'].'</div>';
		}else{
			echo '<div id="selectbutton-'.$AppID.'" onclick="selectButtonActive'.$AppID.'(false)" style="margin-top:2px;" class="ui-forest-button ui-forest-cancel">'.$explorer_lang['cancelButton'].'</div>';
		}
	}

	//Selector Mode
	if($ExplorerMode == 'selector' && isset($callback)){
		echo '<div id="selectbuttoncallback-'.$AppID.'" onclick="selectButtonCallback'.$AppID.'(\''.$callback.'\')" style="margin: 2px 0px -2px 0; display: none;" class="ui-forest-button ui-forest-accept">'.$explorer_lang['selectButtonFile'].'</div>';
	}

	?>
</div>

<div style="margin-top:7px; border-top:1px solid #d4d4d4; padding-top:7px;">
<div class="ui-forest-blink <? echo "dir-$AppID" ?>" style="padding:4px; background:#4d94ef; margin:0px 10px; border-radius:10px; color:#2b5182; float:left; width:20px;" id="<?echo $back?>" onclick="load<?echo $AppID?>(this)">
	&#9668;
</div>
<input style="-webkit-appearance:none; border:1px solid #ccc; width:80%; font-size:17px; margin: 0 5px 10px;" type="search" value="<?echo $prefix.$pathmain?>"></input>
</div>
</div>
<div id="mkdirdiv<?echo $AppID?>" class="forest-ui-request-box" style="d padding: 20px; text-align: center;">
<label for="mkdirinput<?echo $AppID?>">
	<?echo $explorer_lang['mdir_label']?>
	<input id="mkdirvalue<?echo $AppID?>" style="font-size:20px; margin:10px;" name="mkdirinput<?echo $AppID?>" type="text" value="">
</label>
<span onclick="ShowCloseBox<?echo $AppID?>('#mkdirdiv<?echo $AppID?>');" style="width:70px;" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mdir_cancelbtn']?>
</span>
<span style="width:70px;" onClick="ShowCloseBox<?echo $AppID?>('#mkdirdiv<?echo $AppID?>'); mkdirbtn<?echo $AppID?>();" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mdir_okbtn']?>
</span>
</div>

<div id="mkfilediv<?echo $AppID?>" class="forest-ui-request-box" style="padding: 20px;text-align: center;">
<label for="mkfileinput<?echo $AppID?>">
	<?echo $explorer_lang['mfile_label']?>
	<input id="mkfilevalue<?echo $AppID?>" style="font-size:20px; margin:10px;" name="mkfileinput<?echo $AppID?>" type="text" value="">
</label>
<span onclick="ShowCloseBox<?echo $AppID?>('#mkfilediv<?echo $AppID?>');" style="width:70px;" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mfile_cancelbtn']?>
</span>
<span style="width:70px;" onClick="ShowCloseBox<?echo $AppID?>('#mkfilediv<?echo $AppID?>'); mkfilebtn<?echo $AppID?>();" class="ui-button ui-widget ui-corner-all">
	<?echo $explorer_lang['mfile_okbtn']?>
</span>
</div>

<div id="explorer-container<?echo $AppID?>" style="margin: 92px 0;">
<?
$countState = true;
$objectArray = array();

function isInArray($array, $search)
{
	foreach ($array as $value) {
			if ($value == $search) {
				return true;
			}
	}
	return false;
}

while (false !== ($entry = $d->read())) {
	$entry = iconv( "UTF8", "UTF8//TRANSLIT", $entry );

	// rename if contain whitespace
	if( preg_match( '/\s+/', $entry ) ){
		$rename_whitespace = $d->path.'/'.$entry;
		rename( $rename_whitespace, str_replace( ' ', '_', $rename_whitespace ) );
		$entry = str_replace( ' ', '_', $entry );
	}

	if(!empty($ShowOnly)){
		if(is_file($entry) && !isInArray($ShowOnly, pathinfo($entry, PATHINFO_EXTENSION)) ){
			$entry = '';
		}
	}

	$path	=	$d->path;
	$name	=	convert( $entry );
	if ($entry	!=	'..' && !empty($entry)){
		$color	=	'transparent';
		$extension	=	'';
		$type	=	$Folder.'assets/folderico.png?h='.$hashImage;
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

	if(preg_match('/'.$_SESSION["loginuser"].'\/trash/',$pathmain)){
		?>
		<div id="erasetrash<?echo $AppID?>" onClick="erasetrash<?echo $AppID?>();" class="ui-forest-button ui-forest-cancel" style="margin:5px; padding:64px 10px; float:left; display:none; height:14px;">
			<b><?echo $explorer_lang['trash_label']?></b>
		</div>
		<script>
		$('#erasetrash<?echo $AppID?>').css('display','block');
		</script>
		<?
	}

	if(is_file(realpath($entry)) && !empty($entry)){
		$object	=	$dialogexplorer;
		$color = 'rgba(0,0,0,0)';
		if($name == 'main.php'){
			if(file_exists('app.png')){
				$hashfileprefix	= $faction->filehash('app.png','false');
				$type	=	$pathmain.'/'.$hashfileprefix;
			}else{
				$type	=	'system/core/design/images/app.png';
			}
			$extension	=	"";
		}else{
			$path_parts = pathinfo($name);
			$extension	=	$path_parts['extension'];
			$type	=	$Folder.'assets/fileico.png?h='.$hashImage;
			if($extension	==	'png'  || $extension	==	'jpg' || $extension	==	'jpeg' || $extension	==	'svg' || $extension	==	'bmp' || $extension	==	'gif'){
				$color = 'transparent';
				$hashfileprefix	= $faction->filehash($entry,'false');
				$type	=	$pathmain.'/'.$hashfileprefix;
				$extension	=	"";
			}

			if(preg_match('%manifest%', $entry)){
				$color = 'transparent';
				$json = json_decode(file_get_contents('http://'.$_SERVER['SERVER_NAME'].$pathmain.'/'.$entry), true);
				$hashfileprefix	= $faction->filehash($_SERVER['DOCUMENT_ROOT'].$pathmain.'/'.array_shift($json['icons']),'false');
				$type	=	str_replace($_SERVER['DOCUMENT_ROOT'], '', $hashfileprefix);
				if(empty($type)){
					$type = 'system/core/design/images/app.png';
				}
				$extension	=	"";
			}

		}
		$fo->format(filesize(realpath($entry)));
		$datecreate = $explorer_lang['date'].': '.date('d.m.y H:i:s', filectime(realpath($entry))).'<br> '.$explorer_lang['size'].': '.$format;
	}

	if($countState){
		$wardir = $_SERVER['DOCUMENT_ROOT'];
		$wardir = stristr($wardir, 'public_html');
		$wardir	= str_replace('public_html/','',$wardir);
	}

	if (!empty($entry) && $entry != '.' && $entry != '..' && !in_array($entry, $warfile) && realpath($entry).'/'.$wardir != $_SERVER['DOCUMENT_ROOT']){
		$select	=	'select'.$AppID.'(\''.md5($name).'\',\''.convert(realpath($entry)).'\',\''.$type.'\',\''.$name.'\');';
		$load = 'load'.$AppID.'(this);';
		$n_color	=	'#000';
		if(eregi('system/users/',realpath($entry)) || eregi('system/core',realpath($entry))){
			if($_SESSION['superuser'] != $_SESSION['loginuser'] && !eregi('system/users/'.$_SESSION['loginuser'],realpath($entry)) || $_SESSION['superuser'] != $_SESSION['loginuser'] && eregi('system/core',realpath($entry))){
			$select	=	'';
			$load = '';
			$n_color	=	'#e63030';
		}
	}

	//is mobile?
	if($isMobile == 'true' && empty($_GET['select']) || $_GET['select'] == 'false'){
		$action = 'click';
		$selectAction = 'ondblclick="'.$select.'"';
	}else{
		$action = 'dblclick';
		$selectAction = 'onclick="'.$select.'"';
	}

	//what is type object
	if(!is_file(realpath($entry))){
		$typeObject = 'dir';
	}else{
		$typeObject = 'file';
	}

	$objectArray[$typeObject] [] = urlencode(
		'<div id="'.convert(realpath($entry)).'" class="'.md5($name).'-'.$AppID.' select-'.$AppID.' '.$typeObject.'-'.$AppID.' ui-button ui-widget ui-corner-all explorer-object" '.$selectAction.' on'.$action.'="'.$load.'" appid="'.$AppID.'"  style="cursor:default; height:128px;	margin:5px;	text-align:center;	width:128px;	position:relative;	display:block;	text-overflow:ellipsis;	overflow:hidden;	float:left; transition:all 0.05s ease-out;" title="'.$name.'">
		<div style="cursor:default; width:80px; height:80px; background-image: url('.$type.'); background-size:cover; -webkit-user-select:none; user-select:none; padding:5px; background-color:'.$color.'; margin:auto;">
		<div style="margin-top:22px; color:#d05858; font-size:17px; font-weight:900;">
		'.$extension.'
		</div>
		</div>
		<div style="text-overflow: ellipsis;overflow: hidden;font-size: 15px;">
		<span style="color:'.$n_color.'; white-space:nowrap;">
		'.$name.'
		</span>
		<div style="font-size:10px; padding:5px; color:#688ad8;">
		'.$datecreate.'
		</div>
		</div>
		</div>');
}

$countState = false;
}
$dir->close;

//show dir first
foreach($objectArray as $type => $object){
	if($type == 'dir'){
		foreach($object as $dirObject){
			echo urldecode($dirObject);
		}
	}
}


//show files
foreach($objectArray as $type => $object){
	if($type == 'file'){
		foreach($object as $fileObject){
			echo urldecode($fileObject);
		}
	}
}

unset($objectArray);
?>
</div>
<div id="<? echo convert(realpath($entry)) ?>" class="<? echo "dir-$AppID" ?> " restrict<? echo $AppID ?>="<? echo "e-restrict-$AppID" ?>" style="position: static; width: 100%; height: 80%;">
</div>
<div id="upload<?echo $AppID?>" class="forest-ui-request-box" style="padding: 20px;">
</div>

<div style="padding:0 10px; background-color: rgba(0, 0, 0, 0); width:97%; top:97%; word-wrap:break-word; font-size:10px; float:right; position:absolute; text-align:right;">
<?
$fo->size_check(dirname(dirname(dirname(__DIR__))));
$fo->format($size);
echo $explorer_lang['use_label'].': '.$format;
?>
</div>
<?
$AppContainer->EndContainer();
?>
<script>

function ShowCloseBox<?echo $AppID?>(boxid){
	if($(boxid).is( ":hidden" )){
		$(boxid).slideDown("fast");
		$("#explorer-container<?echo $AppID?>").css('filter', 'grayscale(1)');
		$("#app<?echo $AppID?>").unbind("keydown");
	}else{
		$(boxid).slideUp("fast");
		$("#explorer-container<?echo $AppID?>").css('filter', 'grayscale(0)');
		$("#app<?echo $AppID?>").bind("keydown");
	}
}

function ShowLoad<?echo $AppID?>(){
	$("#upload<?echo $AppID?>").slideDown("fast");
	$("#explorer-container<?echo $AppID?>").css('filter', 'grayscale(1)');
	$("#app<?echo $AppID?>").unbind("keydown");
}


<?

	//Execute Function Request
	$AppContainer->ExecuteFunctionRequest();

	// load dir
	$AppContainer->Event(
		"load",
		'object',
		$Folder,
		'main',
		array(
			'mobile' => $isMobile,
			'dir' => '"+object+"',
			'select' => $_GET['select'],
			'explorermode' => $ExplorerMode,
			'callback' => $callback,
			'showonly' => $_ShowOnly
		),
		'if(typeof object === \'string\' || object instanceof String){object = object;}else{object = object.id;} $("#app'.$AppID.'").unbind("keydown")',
		0
	);

	// make link
	$AppContainer->Event(
		"link",
		'object',
		$Folder,
		'main',
		array(
			'linkdir' => '"+object.id+"',
			'ico' => '"+object.getAttribute(\'ico\')+"',
			'linkname' => '"+object.getAttribute(\'link\')+"',
			'dir' => realpath($entry),
			'select' => $_GET['select'],
			'explorermode' => $ExplorerMode,
			'callback' => $callback,
			'showonly' => $_ShowOnly
		)
	);

	// show load div
	$AppContainer->Event(
		"loadshow",
		'object',
		$Folder,
		'uploadwindow',
		array(
			'where' => '"+object.id+"',
			'select' => $_GET['select'],
			'explorermode' => $ExplorerMode,
			'callback' => $callback,
			'showonly' => $_ShowOnly
		),
		'ShowLoad'.$AppID.'();',
		1,
		"upload$AppID"
	);

	// erase trash
	$AppContainer->Event(
		"erasetrash",
		NULL,
		$Folder,
		'main',
		array(
			'erasestatus' => 'true',
			'dir' => realpath($entry),
			'select' => $_GET['select'],
			'explorermode' => $ExplorerMode,
			'callback' => $callback,
			'showonly' => $_ShowOnly
		)
	);

	// make dir button
	$AppContainer->Event(
		"mkdirbtn",
		NULL,
		$Folder,
		'main',
		array(
			'makedir' => '"+escape($("#mkdirvalue'.$AppID.'").val())+"',
			'dir' => realpath($entry),
			'select' => $_GET['select'],
			'explorermode' => $ExplorerMode,
			'callback' => $callback,
			'showonly' => $_ShowOnly
		)
	);

	// make file button
	$AppContainer->Event(
		"mkfilebtn",
		NULL,
		$Folder,
		'main',
		array(
			'makefile' => '"+escape($("#mkfilevalue'.$AppID.'").val())+"',
			'dir' => realpath($entry),
			'select' => $_GET['select'],
			'explorermode' => $ExplorerMode,
			'callback' => $callback,
			'showonly' => $_ShowOnly
		)
	);

	// new load ???
	$AppContainer->Event(
		"newload",
		'key,value',
		$Folder,
		'main',
		array(
			'"+key+"' => '"+value+"',
			'dir' => realpath($entry),
			'select' => $_GET['select'],
			'explorermode' => $ExplorerMode,
			'callback' => $callback,
			'showonly' => $_ShowOnly
		)
	);

	// reload
	$AppContainer->Event(
		"reload",
		NULL,
		$Folder,
		'main',
		array(
			'dir' => realpath($entry),
			'select' => $_GET['select'],
			'explorermode' => $ExplorerMode,
			'callback' => $callback,
			'showonly' => $_ShowOnly
		)
	);

	// select | deselect
	$AppContainer->Event(
		"selectButtonActive",
		'state',
		$Folder,
		'main',
		array(
			'dir' => realpath($entry),
			'select' => '"+state+"',
			'explorermode' => $ExplorerMode,
			'callback' => $callback,
			'showonly' => $_ShowOnly
		)
	);
?>

//send callback
function selectButtonCallback<?echo $AppID?>(name){
	var get_file_callback = $(".loadthis<?echo $AppID?>").attr("id");
	var get_name_callback = $(".mklink").attr("link");
	if ($("div["+name+"]").length){
		$( "div["+name+"]" ).attr(name, get_file_callback);
		$( "div["+name+"]" ).text(get_name_callback);
	}else if ($("input["+name+"]").length) {
		$( "input["+name+"]" ).attr(name, get_file_callback);
		$( "input["+name+"]" ).val(get_file_callback);
	}

	$("#process<?echo $AppID?>").remove();
}

//show select object property
function getproperty<?echo $AppID?>(object){
	if(typeof object === 'string' || object instanceof String){
		object = object;
	}else{
		object = object.id;
	}
	makeprocess('<?echo $Folder?>property.php', object, 'object', '<?echo $explorer_lang['menu_property_label']?>');
};


var enterfolder;
var backfolder = "<?echo $back?>";
let rightfolder = null;
let leftfolder = null;

var keycode = null;
var e = null;

function select<?echo $AppID?>(folder, folder2, folder3, folder4){
	$(".select-<?echo $AppID?>").css('background-color','transparent');
	$('.'+folder+'-<?echo $AppID?>').css('background-color','#d4d4d4');
	$("#selectbuttoncallback-<?echo $AppID?>").css('display','block');
	$(".loadthis<?echo $AppID?>").attr("id",folder2);
	$(".loadas").attr("id",folder2);
	$(".mklink").attr("id",folder2);
	$(".mklink").attr("ico",folder3);
	$(".mklink").attr("link",folder4);
	enterfolder = folder2;

	if($('.'+folder+'-<?echo $AppID?>')){

		//get right folder class
		rightfolder = $('.'+folder+'-<?echo $AppID?>').next('.select-<?echo $AppID?>');
		if(rightfolder.attr('class')){
			rightfolder = rightfolder.attr('class').split(' ')[0];
		}else{
			rightfolder = $('.select-<?echo $AppID?>').attr('class').split(' ')[0];
		}

		//get left folder class
		leftfolder = $('.'+folder+'-<?echo $AppID?>').prev('.select-<?echo $AppID?>');
		if(leftfolder.attr('class')){
			leftfolder = leftfolder.attr('class').split(' ')[0];
		}else{
			leftfolder = $('.select-<?echo $AppID?>').last().attr('class').split(' ')[0];
		}

	}
};

function mkdirshow<?echo $AppID?>(){
	ShowCloseBox<?echo $AppID?>("#mkdirdiv<?echo $AppID?>");
	$("#mkdirvalue<?echo $AppID?>").focus();
	$("#mkdirvalue<?echo $AppID?>").val('');
};

function mkfileshow<?echo $AppID?>(){
	ShowCloseBox<?echo $AppID?>('#mkfilediv<?echo $AppID?>');
	$("#mkfilevalue<?echo $AppID?>").focus();
	$("#mkfilevalue<?echo $AppID?>").val('');
};

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

function copy<?echo $AppID?>(file){
	if($(".loadthis<?echo $AppID?>").attr("id")){
		localStorage.setItem('copy', file);
		checkbutton();
	}
};

function paste<?echo $AppID?>(file, dir = null){
	let _dir;

	if(dir){
		_dir = dir + '/';
	}else{
		_dir = "<?echo convert(realpath($entry)).'/'?>";
	}

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
			 n:_dir,
			 a:action
		}
	}).done(function(o) {
		reload<?echo $AppID?>();
		_dir = null;
});
	checkbutton();
};

function cut<?echo $AppID?>(file){
	localStorage.removeItem('copy');
	localStorage.setItem('cut', file);
	checkbutton();
};

//make every object draggable
$(".explorer-object").draggable({
	opacity: 0.7,
	helper: "clone",
	zIndex: "10000",
	appendTo: "#proceses"
});

//make every dir droppable
$(".dir-<?echo $AppID?>").droppable({
	accept: ".explorer-object",
	drop: function(event, ui){
		let appid_ = ui.draggable.attr('appid');
		if($(this).attr('restrict<? echo $AppID ?>') != "e-restrict-"+appid_){
			localStorage.removeItem('copy');
			localStorage.setItem('cut', ui.draggable.attr('id'));
			paste<?echo $AppID?>(ui.draggable.attr('id'), $(this).attr('id'));
			ui.draggable.remove();
		}
	}
});

$(function(){
	$("#editmenu<?echo $AppID?>").menu();
	$("#mmenu<?echo $AppID?>").menu();
	$("#makeprocess").remove();
});

function reloadApp<?echo $AppID?>(){
	reload<?echo $AppID?>();
}

var map<?echo $AppID?> = {
	'16': false,
	'17': false,
	'18': false,
	'46': false,
	'67': false,
	'70': false,
	'78': false,
	'79': false,
	'80': false,
	'86': false,
	'88': false
};

	$("#app<?echo $AppID ?>").bind('keydown', function(e){
		if($("#app<?echo $AppID?>").hasClass('windowactive')){
			var keycode = (e.keyCode ? e.keyCode : e.which);

			//check if enter pressed
			if(enterfolder){
				if(keycode == '13'){
					load<?echo $AppID?>(enterfolder);
					keycode = null;
					enterfolder = null;
					e = null;
				}
			}

			//check if back pressed
			if(backfolder){
				if(keycode == '8'){
					load<?echo $AppID?>(backfolder);
					keycode = null;
					backfolder = null;
					e = null;
				}
		}

		//check if right pressed
		if(keycode == '39'){
			if(rightfolder){
				if($("."+rightfolder).trigger('click')){
					let keycode = null;
					let e = null;
				}
			}else{
				if($('.select-<?echo $AppID?>').attr('class')){
					rightfolder = $('.select-<?echo $AppID?>').attr('class').split(' ')[0]
					if($("."+rightfolder).trigger('click')){
						let keycode = null;
						let e = null;
					}
				}
			}
		}

		//check if left pressed
		if(keycode == '37'){
			if(leftfolder){
				if($("."+leftfolder).trigger('click')){
					let keycode = null;
					let e = null;
				}
			}else{
				if($('.select-<?echo $AppID?>').attr('class')){
					leftfolder = $('.select-<?echo $AppID?>').attr('class').split(' ')[0]
					if($("."+leftfolder).trigger('click')){
						let keycode = null;
						let e = null;
					}
				}
			}
		}

		if(e){
			if(e.keyCode in map<?echo $AppID?>){
				map<?echo $AppID?>[e.keyCode] = true;
				folder_subject = $(".loadthis<?echo $AppID?>").attr("id");

				//copy keycode
				if(map<?echo $AppID?>['17'] && map<?echo $AppID?>['67']){
					copy<?echo $AppID?>(folder_subject);
				}

				//paste keycode
				if(map<?echo $AppID?>['17'] && map<?echo $AppID?>['86']){
					paste<?echo $AppID?>(folder_subject);
				}

				//cut keycode
				if(map<?echo $AppID?>['17'] && map<?echo $AppID?>['88']){
					cut<?echo $AppID?>(folder_subject);
				}

				//delete keycode
				if(map<?echo $AppID?>['17'] && map<?echo $AppID?>['46']){
					$( ".trashtrigger<?echo $AppID?>" ).trigger( "click" );
				}

				//delete forever keycode
				if(map<?echo $AppID?>['16'] && map<?echo $AppID?>['46']){
					$( ".deletetrigger<?echo $AppID?>" ).trigger( "click" );
				}


				//new file keycode
				if(map<?echo $AppID?>['16'] && map<?echo $AppID?>['78']){
					mkfileshow<?echo $AppID?>();
				}

				//new folder keycode
				if(map<?echo $AppID?>['16'] && map<?echo $AppID?>['70']){
					mkdirshow<?echo $AppID?>();
				}

				//open keycode
				if(map<?echo $AppID?>['16'] && map<?echo $AppID?>['79']){
					load<?echo $AppID?>(folder_subject);
				}

				//show property keycode
				if(map<?echo $AppID?>['16'] && map<?echo $AppID?>['80']){
					getproperty<?echo $AppID?>(folder_subject);
				}

			}
		}

	}else{
		let keycode = null;
		enterfolder = null;
		backfolder = null;
		rightfolder = null;
		leftfolder = null;
		let e = null;
	}
}).keyup(function(e){
	if(e.keyCode in map<?echo $AppID?>){
		map<?echo $AppID?>[e.keyCode] = false;
	}
});


$('#<?echo $AppName.$AppID?>').bind('dragenter', function(event) {
	if (event.type == 'dragenter') {
		if($(".upload-container<? echo $AppID ?>").length == 0){
			$('.load-class<?echo $AppID?>').trigger("click");
		}else{
			$('#<?echo $AppName.$AppID?>').unbind('dragenter');
		}
	}
});


checkbutton();
</script>
<style>.ui-menu{width: 150px;}</style>
