<?
/*Rich Text Editor*/

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;

/* App Info */
$AppContainer->AppNameInfo = 'Rich Text Editor';
$AppContainer->SecondNameInfo = 'Rich Text Editor';
$AppContainer->VersionInfo = '1.0.2';
$AppContainer->AuthorInfo = 'codepen.io';

/* Container Info */
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->LibraryArray = array('gui', 'permissions');
$AppContainer->height = '100%';
$AppContainer->width = '100%';
$AppContainer->customStyle = 'min-width:300px; min-height:200px; overflow:hidden;';
$AppContainer->StartContainer();

$rteloader = str_replace($_SERVER['DOCUMENT_ROOT'], '', $_GET['defaultloader']);
$mode = '1';
if(empty($rteloader)){
  $rteloader  = $_GET['rteloader'];
  $mode = '0';
}
?>

<link rel='stylesheet prefetch' href='https://netdna.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.css'>

<?php

/*--------Делаем запрос--------*/
$newpermission = new PermissionRequest;
$object = new gui;
$newpermission->fileassociate(array('txt','md'), $Folder.'main.php', 'rteloader', $AppName);

?>
<style>
.editor {
  box-shadow: 0 0 2px #CCC;
  min-height: 150px;
  overflow: auto;
  padding: 1em;
  margin: 20px 10px;
  resize: vertical;
  outline: none;
  background: var(--fo-win-body, #fff);
  white-space: pre-wrap;
}


.toolbar {
  text-align: center;
	width: 65%;
	margin: auto;
}

#editor<?echo $AppID?> a{
	color: #f44336;
}

.toolbar a,
.fore-wrapper,
.back-wrapper {
  border: 1px solid #AAA;
  background: var(--fo-win-body, #fff);
  border-radius: 1px;
  color:var(--fo-text, #000);
  padding: 5px;
  margin: -2px;
  margin-top: 10px;
  display: inline-block;
  text-decoration: none;
  box-shadow: 0px 1px 0px #CCC;
}

.toolbar a:hover, .fore-wrapper:hover, .back-wrapper:hover {
  background: #f2f2f2;
  border-color: #8c8c8c;
}

a[data-command='redo'],a[data-command='strikeThrough'],a[data-command='justifyFull'],a[data-command='insertOrderedList'],a[data-command='outdent'],a[data-command='p'],a[data-command='superscript']{
  margin-right:5px;
  border-radius:0 3px 3px 0;
}

a[data-command='undo'],.fore-wrapper,a[data-command='justifyLeft'],a[data-command='insertUnorderedList'],a[data-command='indent'],a[data-command='h1'],a[data-command='subscript']{
  border-radius:3px 0 0 3px;
}

a.palette-item {
  height: 1em;
  margin: 2px 1px;
  width: 1em;
	border: none;
	box-shadow: none;
}

.fore-palette,
.back-palette {
  display: none;
}

.fore-wrapper,
.back-wrapper {
  display: inline-block;
  cursor: pointer;
}

.fore-wrapper:hover .fore-palette,
.back-wrapper:hover .back-palette {
  display: block;
  float: left;
  position: absolute;
  padding: 3px;
  width: 10em;
  background: var(--fo-win-body, #fff);
  border: 1px solid #DDD;
  box-shadow: 0 0 5px #CCC;
  height: 4.4em;
}

.fore-wrapper:hover .fore-palette {
  left: 34%;
  top: 42px;
}

.back-wrapper:hover .back-palette {
  left: 36%;
  top: 42px;
}

.fore-palette a,
.back-palette a {
  background: var(--fo-win-body, #fff);
  margin-bottom: 2px;
}
</style>
<link href='https://fonts.googleapis.com/css?family=Dosis|Candal' rel='stylesheet' type='text/css'>
<div>
<div id="toolbar<?echo $AppID?>" class="toolbar">
<a href="#" data-command='undo'><i class='fa fa-undo'></i></a>
<a href="#" data-command='redo'><i class='fa fa-repeat'></i></a>
<div class="fore-wrapper"><i class='fa fa-font' style='color:#2196f3;'></i>
  <div class="fore-palette" style="top:80px; left:100px;">
  </div>
</div>
<div class="back-wrapper"><i class='fa fa-font' style='background:#2196f3; color:#fff;'></i>
  <div class="back-palette" style="top:80px; left:120px;">
  </div>
</div>
<a href="#" data-command='bold'><i class='fa fa-bold'></i></a>
<a href="#" data-command='italic'><i class='fa fa-italic'></i></a>
<a href="#" data-command='underline'><i class='fa fa-underline'></i></a>
<a href="#" data-command='strikeThrough'><i class='fa fa-strikethrough'></i></a>
<a href="#" data-command='justifyLeft'><i class='fa fa-align-left'></i></a>
<a href="#" data-command='justifyCenter'><i class='fa fa-align-center'></i></a>
<a href="#" data-command='justifyRight'><i class='fa fa-align-right'></i></a>
<a href="#" data-command='justifyFull'><i class='fa fa-align-justify'></i></a>
<a href="#" data-command='indent'><i class='fa fa-indent'></i></a>
<a href="#" data-command='outdent'><i class='fa fa-outdent'></i></a>
<a href="#" data-command='insertUnorderedList'><i class='fa fa-list-ul'></i></a>
<a href="#" data-command='insertOrderedList'><i class='fa fa-list-ol'></i></a>
<a href="#" data-command='h1'>H1</a>
<a href="#" data-command='h2'>H2</a>
<a href="#" data-command='createlink'><i class='fa fa-link'></i></a>
<a href="#" data-command='unlink'><i class='fa fa-unlink'></i></a>
<a href="#" data-command='insertimage'><i class='fa fa-image'></i></a>
<a href="#" data-command='p'>P</a>
<a href="#" data-command='subscript'><i class='fa fa-subscript'></i></a>
<a href="#" data-command='superscript'><i class='fa fa-superscript'></i></a>
</div>
<div id="editorContainer<?echo $AppID?>" style="display:grid; grid-template-rows: 57% 30%; height:100%;">
	<div id='editor<?echo $AppID?>' class="editor" contenteditable="true"><?
		if(isset($rteloader)){
  		if($mode == '1'){
  		echo htmlentities(file_get_contents($_SERVER['DOCUMENT_ROOT'].$rteloader));
		}else{
  		echo file_get_contents($_SERVER['DOCUMENT_ROOT'].$rteloader);
		}
		}
		?></div>
		<div>
			<div onClick='save<?echo $AppID?>()' style="width: auto;" class="ui-forest-button ui-forest-accept ui-forest-left"><span>Save</span></div>
			<div id="changeModeButton<?echo $AppID?>" onClick='changemode<?echo $AppID?>()' style="width: auto;" class="ui-forest-button ui-forest-cancel ui-forest-left">View as a Document</div>
		</div>
</div>
</div>

<?
$AppContainer->EndContainer();
?>

<script>
/*--------Логика JS--------*/
var colorPalette = ['000000', 'FFFFFF', '2196F3', 'F44336', '673AB7', '009688', 'FFEB3B', '4CAF50', 'E91E63', '795548'];
var forePalette = $('.fore-palette');
var backPalette = $('.back-palette');

for (var i = 0; i < colorPalette.length; i++) {
  forePalette.append('<a href="#" data-command="forecolor" data-value="' + '#' + colorPalette[i] + '" style="background-color:' + '#' + colorPalette[i] + ';" class="palette-item"></a>');
  backPalette.append('<a href="#" data-command="backcolor" data-value="' + '#' + colorPalette[i] + '" style="background-color:' + '#' + colorPalette[i] + ';" class="palette-item"></a>');
}

$('.toolbar a').click(function(e) {
  var command = $(this).data('command');
  if (command == 'h1' || command == 'h2' || command == 'p') {
    document.execCommand('formatBlock', false, command);
  }
  if (command == 'forecolor' || command == 'backcolor') {
    document.execCommand($(this).data('command'), false, $(this).data('value'));
  }
    if (command == 'createlink' || command == 'insertimage') {
			url = prompt('Enter the link here: ','http:\/\/');
			document.execCommand($(this).data('command'), false, url);
	}
	else document.execCommand($(this).data('command'), false, null);
});

function changemode<?echo $AppID;?>(){
	if($("#editor<?echo $AppID?>").attr('contenteditable') == 'true'){
		$("#editor<?echo $AppID?>").attr('contenteditable', 'false');
		$("#changeModeButton<?echo $AppID?>").removeClass('ui-forest-cancel');
		$("#changeModeButton<?echo $AppID?>").addClass('ui-forest-accept');
		$("#changeModeButton<?echo $AppID?>").text('Edit Document');
		$("#toolbar<?echo $AppID?>").hide('slide', {direction:"up"}, 300, function(){
			$("#editorContainer<?echo $AppID?>").css('grid-template-rows','87% 30%');
		});
		$("#editor<?echo $AppID?>").css('box-shadow','none');
	}else{
		$("#editor<?echo $AppID?>").attr('contenteditable', 'true');
		$("#changeModeButton<?echo $AppID?>").removeClass('ui-forest-accept');
		$("#changeModeButton<?echo $AppID?>").addClass('ui-forest-cancel');
		$("#changeModeButton<?echo $AppID?>").text('View as a Document');
		$("#toolbar<?echo $AppID?>").show('slide', {direction:"up"}, 300);
		$("#editorContainer<?echo $AppID?>").css('grid-template-rows','57% 30%');
		$("#editor<?echo $AppID?>").css('box-shadow','0 0 2px #ccc');
	}
}

function save<?echo $AppID;?>(){
  var folder = "<? echo $rteloader ?>";
  if(folder != null){
    <?
    if($mode == '0'){
    ?>
    var str = $("#editor<?echo $AppID?>").html();
    <?
  }
    else{
    ?>
    var str = $("#editor<?echo $AppID?>").text();
    <?
    }
    ?>
    $.ajax({
      type: "POST",
      url: "<?echo $Folder?>savecontent",
      data: {
         content: str,
         folder: "<?echo $_SERVER['DOCUMENT_ROOT']?>"+folder
      }
    }).done(function(o) {
      try {
        var r = (typeof o === 'string') ? JSON.parse(o) : o;
        if (r && r.ok) {
          if (window.FO_TOAST) { FO_TOAST('Сохранено: ' + r.file); }
        } else if (r && r.error) {
          if (window.FO_TOAST) { FO_TOAST('Не сохранено: ' + r.error); }
        }
      } catch (e) {
        if (window.FO_TOAST) { FO_TOAST('Сохранено'); }
      }
    }).fail(function(){
      if (window.FO_TOAST) { FO_TOAST('Ошибка сохранения'); }
    });
  }
}


</script>
