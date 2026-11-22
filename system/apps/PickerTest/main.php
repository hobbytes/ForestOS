<?
/* Временное приложение: проверка Mercury AppGUI::SelectFile */
$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

require $_SERVER['DOCUMENT_ROOT'] . '/system/core/library/Mercury/AppContainer.php';

$AppContainer = new AppContainer;
$AppContainer->AppNameInfo = 'PickerTest';
$AppContainer->SecondNameInfo = 'Picker Test';
$AppContainer->VersionInfo = '1.0.0';
$AppContainer->AuthorInfo = 'Forest Media';
$AppContainer->LibraryArray = array('AppGUI');
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '300px';
$AppContainer->width = '520px';
$AppContainer->backgroundColor = '#fbfbfd';
$AppContainer->fontColor = '#151a17';
$AppContainer->customStyle = 'padding:16px;';
$AppContainer->StartContainer();

$AppGUI = new AppGUI;
?>
<div style="font:13px/1.4 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif">
  <div style="font-weight:700;font-size:15px;margin-bottom:10px">Выбор файла через Mercury</div>
  <?
  $AppGUI->SelectFile($AppID, 'file_url', '100%', 'zip', 'путь к архиву', 'Выбрать файл (zip)', '/system/users/admin/documents');
  ?>
  <div id="pick-echo<?echo $AppID?>" style="margin-top:12px;color:#6d747c">значение: <b id="pick-value<?echo $AppID?>">—</b></div>
</div>
<script>
<? $AppGUI->CallbackSelectFile($AppID); ?>
</script>
<?
$AppContainer->EndContainer();
?>
