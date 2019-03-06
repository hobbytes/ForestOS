<?
/* Application Store */

$AppName = $_GET['appname'];
$AppID = $_GET['appid'];
$Folder = $_GET['destination'];

require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/AppContainer.php';

/* Make new container */
$AppContainer = new AppContainer;

/* App Info */
$AppContainer->AppNameInfo = 'Apps House';
$AppContainer->SecondNameInfo = 'Магазин';
$AppContainer->VersionInfo = '1.1';
$AppContainer->AuthorInfo = 'Forest Media';

/* Library List */
$AppContainer->LibraryArray = Array('AppGUI', 'filesystem', 'bd', 'http', 'gui');

/* Container Info */
$AppContainer->appName = $AppName;
$AppContainer->appID = $AppID;
$AppContainer->height = '530px';
$AppContainer->backgroundColor = '#e8e8e8';
$AppContainer->customStyle = 'padding-top:0px; max-width:100%;';
//$AppContainer->showError = true;
$AppContainer->StartContainer();

//load language
$language = parse_ini_file('assets/lang/'.$_SESSION['locale'].'.lang');

/* get data */
$activeTab = 0;
if(isset($_GET['activetab'])){
  $activeTab = $_GET['activetab'];
}

$AppGUI = new AppGUI;
$FileCalc = new filecalc;
$FileAction = new fileaction;
$BD = new readbd;
$HttpRequest = new http;
$gui = new gui;

$server_url = "http://forest.hobbytes.com/media/os/AppsHouse/";

$FUID = $BD->readglobal2("fuid", "forestusers", "login", $_SESSION["loginuser"], true);
$PWD = $BD->readglobal2("password", "forestusers", "login", $_SESSION["loginuser"], true);
$DROOT = $_SERVER['DOCUMENT_ROOT'];
$token = md5($FUID.$DROOT.$PWD);

$OSInfo = parse_ini_file('../../core/osinfo.foc', false);
$CurrentVersionOS = $OSInfo['subversion'];

function UTFTransform($text){
  return preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))', $text);
}

$AppSearch = $AppContainer->GetAnyRequest('AppSearch');

if(!empty($AppSearch)){
  $_AppSearch = UTFTransform($AppSearch);
}


?>

<link rel="stylesheet" type="text/css" href="<? echo $Folder.$FileAction->filehash("assets/main.css") ?>">

<div id="Tabs<?echo $AppID?>">
  <ul>
    <li><a href="#Apps<?echo $AppID?>"><? echo $language['app_tab'] ?></a></li>
    <li><a href="#Control<?echo $AppID?>"><? echo $language['control_tab'] ?></a></li>
    <li><a href="#Updates<?echo $AppID?>"><? echo $language['update_tab'] ?></a></li>
  </ul>

<div id="Apps<? echo $AppID ?>" style="margin: 0 auto; overflow: auto;">
  <div id="TabTitle"><? echo $language['app_tab'] ?></div>

  <div style="text-align: center; border-bottom: 1px solid #d4d4d4;">
    <input style="-webkit-appearance: none; padding: 5px; border: 1px solid #ccc; border-radius: 6px; width: 270px; margin-bottom: 20px;" id="AppSearch<? echo $AppID ?>" type="search" value="<? echo $_AppSearch ?>" placeholder="<? echo $language['placeholder_search'] ?>">
    <div class="ui-forest-blink" onClick="AppSearch<? echo $AppID ?>()" style="display: inline-block; background: #2196F3; color: #fff; padding: 5px; border-radius: 6px; cursor: default;"><? echo $language['button_search'] ?></div>
  </div>

  <div style="margin-top: 15px">
    <?

    /* install app */

    if(isset($_GET['install_app_hash'])){
      $AppDestination = "http://forest.hobbytes.com/media/os/AppsHouse/Apps/".$_GET['install_app_hash']."/app.zip";

      if(!is_dir('./temp/')){
        mkdir('./temp/');
      }

      $updateMode = false;
      if($_GET['install_app_update_mode'] == 'true'){
        $updateMode = true;
      }

      $curlCh = curl_init();
      curl_setopt($curlCh, CURLOPT_URL, $AppDestination);
      curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curlCh, CURLOPT_SSLVERSION,3);
      $curlData = curl_exec($curlCh);
      curl_close($curlCh);

      $TempFile = './temp/'.$_GET['install_app_hash'].'.zip';
      $file = fopen($TempFile, "w+");
      fputs($file, $curlData);
      fclose($file);

      $zip = new ZipArchive;
      if($zip->open($TempFile) === TRUE){
        $zip->extractTo('../');
        $zip->close();
        unlink($TempFile);
        file_put_contents('../'.$_GET['install_app_name'].'/app.hash', $_GET['install_app_hash']);

        if($_SESSION['locale'] == 'en'){
          $pubname = $_GET['install_app_name'];
        }else{
          $pubname = UTFTransform($_GET['install_app_second_name']);
        }

        $pubname = str_replace('_', ' ', $pubname);

        if(!$updateMode){
          $LinkFile = $_SERVER['DOCUMENT_ROOT'].'/system/users/'.$_SESSION['loginuser'].'/desktop/'.$_GET['install_app_name'].'_'.uniqid().'.link';
          $FileAction->makelink($LinkFile, $_SERVER['DOCUMENT_ROOT'].'/system/apps/'.$_GET['install_app_name'].'/', 'main', '', $app_link, $pubname, $pubname, 'system/apps/'.$_GET['install_app_name'].'/app.png');

          if(is_file('../'.$_GET['install_app_name'].'/app.hash')){
            $HttpRequest->makeNewRequest($server_url.'StatApp.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token", 'hash' => $_GET['install_app_hash'], 'action' => 'install'));
          }

          $InstallCaption_1 = $language['install_caption_1'];
          $InstallCaption_2 = $language['install_caption_2'];
        }else{
          $InstallCaption_1 = $language['update_caption_1'];
          $InstallCaption_2 = $language['update_caption_2'];
        }

        $gui->newnotification(UTFTransform($AppName), $InstallCaption_1.' '.$language['install_n_1'], $language['install_n_2'].' <b>'.$pubname.'</b> '.$InstallCaption_2);

      }
    }

    function showError($data){
      if(!empty($data)){
        echo '<div style="padding: 10px;background: #e0645b;color: #6d2620;font-weight: 900;margin: 10px 0;border: 3px dashed;">';
        echo 'Error:<br>';
        echo '<ul>';
        foreach ($data as $key => $value) {
          echo '<li>'.$value.'</li>';
        }
        echo '</ul>';
        echo '</div>';
      }
    }

    /* make few requets */

    $ScrollTo = $AppContainer->GetAnyRequest('ScrollTo', 0);
    $GetPage = $AppContainer->GetAnyRequest('Page', 12);

    $GetApps = $HttpRequest->makeNewRequest($server_url.'GetApp.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token", 'page' => $GetPage, 'search' => $AppSearch));
    $GetApps = json_decode($GetApps, TRUE);

    $MaxRating = $HttpRequest->makeNewRequest($server_url.'MaxRating.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token"));
    /* create array with installed apps */

    $InstalledApps = array();

    foreach (glob("../*/main.php") as $filename) {
      $filename = str_replace(array('..','/','main.php'), '', $filename);
      $InstalledApps[] = $filename;
    }

    /* rating function */
    function getRating($rating, $MaxRating){
      $r = ($rating / $MaxRating) * 5;
      $r_int = intval($r);
      $r_null = 5 - $r_int;
      $dots = NULL;

      for ($i = 0; $i < $r_int; $i++){
        $dots .= '<div class="AppTile-rating" ></div>';
      }

      for ($i = 0; $i < $r_null; $i++){
        $dots .= '<div class="AppTile-rating AppTile-rating-null" ></div>';
      }

      return $dots;
    }

    /* show available apps */

    foreach ($GetApps['Apps'] as $key) {

      if($_SESSION["locale"]  ==  'en'){
      	$AppName_ = str_replace('_', ' ', $key['name']);
    	}else{
      	$AppName_ = str_replace('_', ' ', $key['second_name']);
    	}
      $AppHash = $key['hash'];

      //print_r($key);

      $rating = $language['rating_caption'].': '.getRating($key['rating'], $MaxRating).' ('.$key['rating'].')';

      $TempIconHash = md5($AppHash.$key['version']);
      $AppIcon = $server_url.'Apps/'.$key['hash'].'/app.png?h='.$TempIconHash;

      $FileCalc->format($key['size']*1024);
      $size = $format;
      $description = UTFTransform($key['description']);
      $AppName_ = UTFTransform($AppName_);

      if (in_array($key['name'], $InstalledApps)) {
        $ButtonClass = "A-button-open";
        $ButtonCaption = $language['app_button_open'];
      }else{
        $ButtonClass = "A-button-install";
        $ButtonCaption = $language['app_button_install'];
      }

      echo '<div class="AppTile" onClick="showInfo'.$AppID.'(\''.$AppHash.'\')">';
      echo '<div class="AppTile-icon" style="background-image: url('.$AppIcon.'); ">';
    	echo '</div>';
      echo '<div class="AppTile-info">';
      echo '<div class="AppTile-name">';
      echo $AppName_;
      echo '</div>';
      echo '<span>';
      echo $language['autor_caption'].': '.$key['author'].'<br>';
      echo $language['version_caption'].': '.$key['version'].'<br>';
      echo '<div class="AppTile-rating-container">'.$rating.'</div>';
      echo $language['size_caption'].': '.$size.'<br>';
      echo '</span>';
      echo '</div>';
      echo '</div>';
      echo '<div class="AppTile-full" id="'.$AppHash.'">';
      echo '<div class="AppTile-close ui-forest-blink" onClick="closeInfo'.$AppID.'(\''.$AppHash.'\')"> x ';
      echo '</div>';
      echo '<div class="AppTile-icon" style="background-image: url('.$AppIcon.'); ">';
    	echo '</div>';
      echo '<div class="AppTile-name">';
      echo '<span class="AppTile-strong-name">'.$AppName_.'</span><br>';
      echo '<span style="font-size: 13px; color: #464646;">'.$AppName_.' by '. $key['author'] . ', version: '.$key['version'].'</span>';

      if($CurrentVersionOS < $key['os_version']){
        echo '<div class="AppTile-button A-button-open">';
        echo $language['os_need_update'];
      }else{
        echo '<div update="false"  app="'.$key['name'].'" app_second="'.$key['second_name'].'" hash="'.$AppHash.'" class="AppTile-button '.$ButtonClass.'">';
        echo $ButtonCaption;
      }
      echo '</div>';
      echo '</div>';
      echo '<span style="font-size:13px; color:#464646; font-weight:600; padding-top: 5px;">'.$language['description_caption'].'</span>';
      echo '<div class="AppTile-description">';
      echo $description;
      echo '</div>';
      echo '</div>';
    }
    ?>
  </div>

  <div style="font-size: 20px; width: 100%; text-align: center; float: none; display: inline-block; font-weight: 900;">
  <?

  $MoreApps = $GetPage + 5;
  if($GetApps['Page']['All'] > $GetPage){
    echo '<span style="padding: 0px 5px; cursor: default; background: #03A9F4; color: #fff; border-radius: 10px;" class="ui-forest-blink" onClick="LoadPage'.$AppID.'('.$MoreApps.');">'.$language['more_apps'].'</span>';
  }

  ?>
  </div>

</div>

<div id="Control<? echo $AppID ?>" style="margin: 0 auto;">
  <div id="TabTitle"><? echo $language['control_tab'] ?></div>
  <div style="padding: 10px; border-bottom: 2px dashed #ccc;">
    <?
    echo '
    <div style="text-align:left; margin-bottom: 10px">
    <b style="font-size:20px;">'.$language['my_apps_caption'].'</b>
    </div>
    ';

    $GetUsersApp = $HttpRequest->makeNewRequest($server_url.'GetApp.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token", 'search_field' => 'EditMode'));
    $GetUsersApp = json_decode($GetUsersApp, TRUE);

    echo '<select id="SelectApp'.$AppID.'" style="width:70%; background: #fff; font-size:15px; padding:10px; -webkit-appearance:none; border: 1px solid #ccc;">';
    $CountApps = 0;
    foreach ($GetUsersApp as $key => $value) {
      echo '<option value="'.$value['hash'].'">'.str_replace('_', ' ', $value['name']).'</option>';
      $CountApps++;
    }

    echo '</select>';
    echo '<div id="SelectEditApp'.$AppID.'" onClick="SelectEditApp'.$AppID.'();" class="ui-forest-button ui-forest-accept" style="margin:10 0;"> '.$language['select_app'].' </div>';
    echo '<div style="font-weight: 900;font-variant: all-petite-caps;font-size: 18px;">'.$language['all_my_apps'].': <b>'.$CountApps.'</b></div>';
    ?>
  </div>
  <div style="padding: 10px; border-bottom: 2px dashed #ccc;">
    <?

    $AppEditMode = false;
    if(isset($_GET['select_edit_app'])){
      $AppEditMode = true;
    }

    if($AppEditMode){
      $AppTabCaption = $language['app_tab_mode_1'];
    }else{
      $AppTabCaption = $language['app_tab_mode_2'];
    }

    if($AppEditMode){
      $GetUserApp = $HttpRequest->makeNewRequest($server_url.'GetApp.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token", 'search_field' => 'hash', 'search' => $_GET['select_edit_app']));
      $GetUserApp = json_decode($GetUserApp, TRUE);

      foreach ($GetUserApp['Apps'] as $key) {
        $u_name = UTFTransform(str_replace('_', ' ', $key['name']));
        $u_sname = UTFTransform(str_replace('_', ' ', $key['second_name']));
        $u_version = $key['version'];
        $u_osversion = $key['os_version'];
        $u_description = UTFTransform($key['description']);
      }

    }else{
      $u_name = ''; $u_sname = ''; $u_version = '1.0'; $u_osversion = $_SESSION['os_version']; $u_description = '';
    }

    echo '
    <div style="text-align:left; margin-bottom: 10px">
    <b style="font-size:20px;">'.$AppTabCaption.'</b>
    </div>
    ';

    if(isset($_GET['delete_app'])){
      $DeleteUserApp = $HttpRequest->makeNewRequest($server_url.'DeleteApp.php', 'Forest OS', $data = array('author' => $_SESSION["loginuser"], 'token' => "$token", 'hash' => $_GET['delete_app']));
      $DeleteUserApp = json_decode($DeleteUserApp, TRUE);
      showError($DeleteUserApp);
    }

    if(isset($_GET['name'])){

      $name = strip_tags(str_replace(' ', '_', $_GET['name']));
      $second_name = strip_tags(str_replace(' ', '_', $_GET['second_name']));
      $version = strip_tags($_GET['version']);
      $os_version = strip_tags($_GET['os_version']);
      $description = strip_tags($_GET['description']);
      $file_url = strip_tags($_GET['file_url']);
      $file_url = str_replace($_SERVER['DOCUMENT_ROOT'], 'http://'.$_SERVER['SERVER_NAME'], $file_url);
      $icon_url = strip_tags($_GET['icon_url']);
      $icon_url = str_replace($_SERVER['DOCUMENT_ROOT'], 'http://'.$_SERVER['SERVER_NAME'], $icon_url);
      $hash = md5($name.$_SESSION["loginuser"].$token);

      if($_GET['update'] != 'true'){
        $ServerFile = 'AddApp.php';
      }else{
        $ServerFile = 'UpdateApp.php';
      }

      $GetStatusUpload = $HttpRequest->makeNewRequest(
        $server_url.$ServerFile,
        'Forest OS',
        $data = array(
          'author' => $_SESSION["loginuser"],
          'token' => "$token",
          'name' => "$name",
          'second_name' => "$second_name",
          'version' => "$version",
          'os_version' => "$os_version",
          'description' => "$description",
          'file_url' => "$file_url",
          'icon_url' => "$icon_url",
          'hash' => "$hash",
        )
      );

      $GetStatusUpload = json_decode($GetStatusUpload, TRUE);
      showError($GetStatusUpload);
    }

    echo '<div>'.$language['pub_name'].' ('.$language['pub_lat'].'):</div>';
    $gui->inputslabel('', 'text', 'name'.$AppID, $u_name,'70', $language['pub_name']);

    echo '<div>'.$language['pub_name'].' ('.$language['pub_cyr'].'):</div>';
    $gui->inputslabel('', 'text', 'second_name'.$AppID, $u_sname,'70', $language['pub_name']);

    echo '<div>'.$language['version_caption'].':</div>';
    $gui->inputslabel('', 'text', 'version'.$AppID, $u_version,'70', $language['version_caption']);

    echo '<div>'.$language['pub_os_ver'].':</div>';
    $gui->inputslabel('', 'text', 'os_version'.$AppID, $u_osversion,'70', $language['pub_os_ver']);

    echo '<div>'.$language['description_caption'].':</div>';
    ?>
    <textarea rows="10" id="description<? echo $AppID ?>" placeholder="<? echo $language['pub_desc_caption'] ?>" style="width: 70%; padding: 10px; margin: 10px 0; border: 1px solid #ccc;" name="description"><? echo $u_description ?></textarea>
    <?

    echo '<div>'.$language['install_n_2'].' (zip):</div>';
    $AppGUI->SelectFile($AppID, "file_url", "70%", "zip" , $language['pub_file_caption'].' (zip)', $language['pub_select_file']);

    echo '<div>'.$language['pub_icon'].' (png):</div>';
    $AppGUI->SelectFile($AppID, "icon_url", "70%", "png" , $language['pub_icon_caption'].' (png)', $language['pub_select_file']);

    if(!$AppEditMode){
      echo '<div id="PublishApp'.$AppID.'" onClick="PublishNewApp'.$AppID.'();" class="ui-forest-button ui-forest-accept" style="margin:30 auto;"> '.$language['pub_load_app'].' </div>';
    }else{
      echo '<div id="UpdateApp'.$AppID.'" onClick="UpdateApp'.$AppID.'();" class="ui-forest-button ui-forest-accept" style="margin:30 auto;"> '.$language['pub_update_app'].' </div>';
      echo '<div id="DeleteApp'.$AppID.'" messageTitle="'.$language['delete_mesage_t'].'" messageBody="'.$language['delete_mesage_b'].'" okButton="'.$language['pub_delete_app'].'" cancelButton="'.$language['delete_mesage_cancel'].'" onClick="ExecuteFunctionRequest'.$AppID.'(this, \'DeleteApp'.$AppID.'\')" class="ui-forest-button ui-forest-cancel" style="margin:30 auto;"> '.$language['pub_delete_app'].' </div>';
    }

    ?>
  </div>
</div>

<div id="Updates<? echo $AppID ?>" style="margin: 0 auto; overflow: auto;">
  <div id="TabTitle"><? echo $language['update_tab'] ?></div>
  <div>
    <?

    $UpdatesCount = 0;

    $no_check_apps = array('Apps_House', 'Explorer', 'update', 'Settings');
    $showEmpty = true;

    $OSUpdateURL = file_get_contents("http://forest.hobbytes.com/media/os/update.php");
    $OSUpdateURL = json_decode($OSUpdateURL, true);

    $CondidateVersionOS = $OSUpdateURL['0']['subversion'];

    if($CondidateVersionOS > $CurrentVersionOS){
      $UpdatesCount++;
      $FileCalc->format($OSUpdateURL['0']['size']*1024);
      $showEmpty = false;

      $OSDescription = $OSUpdateURL['0']['description'];

      if(empty($OSDescription)){
        $OSDescription = $language['os_null_description'];
      }

      echo '<div style="color: #ebece6; border-radius: 5px; padding: 20px; border: 1px solid #ccc; background: #2c2d3c; margin: 10px;">';
      echo '<div id="TabTitle" style="font-size: 23px; font-weight: 600; color: #53e579;"> '.$language['os_update_caption'].' </div>';
      echo '<p style="text-align:left; background-image: url(http://forest.hobbytes.com/media/os/updates/uplogo.png); background-size:cover; height:80px; width:80px;"></p>';
      echo '<span style="font-size:17px;"><b>Forest OS</b> '.$OSUpdateURL['0']['codename'].'</span><br>';
      echo '<span style="font-size:12px; font-weight:900; " >'.$language['os_revision'].': <span style="color:#ebece6; text-transform: uppercase;">'.$OSUpdateURL['0']['file'].'</span></span><br>';
      echo '<span style="font-size:12px; ">'.mb_strtolower($language['version_caption']).': '.$OSUpdateURL['0']['version'].'<br>'.$language['os_sub_version'].': '.$OSUpdateURL['0']['subversion'].'<br>'.mb_strtolower($language['size_caption']).': '.$format.'</span></span>';
      echo '<br><br><b>'.$language['description_caption'].':</b><br><div style="font-size:15px; color:#ebece6; white-space:pre-wrap; padding: 4px 0px;">'.$OSDescription.'</div>';
      echo '<div id="'.$OSUpdateURL['0']['file'].'" class="ui-forest-blink" t="app_h" onClick="update'.$AppID.'()" style="background-color:#962439; color:#fff; width:30%; margin: 70px auto 10px auto; font-size:15px; padding:10px; border-radius:5px; text-align:center;">'.$language['pub_update_app'].'</div>';
      echo '</div>';
      echo '<div style="border-bottom: 1px solid #ccc; padding: 10px; margin-bottom: 36px;"></div>';
    }


    $ComparisonApps = array();
    foreach ($InstalledApps as $key){
      if(!in_array($key, $no_check_apps)){
        $hash = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/system/apps/'.$key.'/app.hash');

        if(!empty($hash)){
          $ComparisonApps[] = $hash;
        }
      }
    }

    $GetComApps = $HttpRequest->makeNewRequest($server_url.'Comparison.php', 'Forest OS', $data = array('login' => $_SESSION["loginuser"], 'token' => "$token", 'data' => json_encode($ComparisonApps)));
    $GetComApps = json_decode($GetComApps, TRUE);

    foreach ($InstalledApps as $key){
      if(!in_array($key, $no_check_apps) && !empty($GetComApps[$key]['name'])){
        $info = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/system/apps/'.$key.'/main.php?getinfo=true&h='.md5(date('dmyhis')));
  			$arrayInfo = json_decode($info);
  	    $curversion	=	$arrayInfo->{'version'};

  			if(empty($curversion)){
  				$curversion = '1.0';
  			}

        $newversion = $GetComApps[$key]['version'];

        if($newversion > $curversion && !empty($GetComApps[$key]['hash'])){
          $UpdatesCount++;
          $showEmpty = false;
          $FileCalc->format($GetComApps[$key]['size']*1024);
          $size = $format;
          $description = UTFTransform($GetComApps[$key]['description']);
          $TempIconHash = md5($GetComApps[$key]['hash'].$GetComApps[$key]['version']);
          $AppIcon = $server_url.'Apps/'.$GetComApps[$key]['hash'].'/app.png?h='.$TempIconHash;

          echo '<div style="border-bottom: 1px solid #ccc;">';
          echo '<div class="AppTile">';
          echo '<div class="AppTile-icon" style="background-image: url('.$AppIcon.'); ">';
        	echo '</div>';
          echo '<div class="AppTile-info">';
          echo '<div class="AppTile-name">';
          echo str_replace('_', ' ', $GetComApps[$key]['name']);
          echo '</div>';
          echo '<span>';
          echo $language['autor_caption'].': '.$GetComApps[$key]['author'].'<br>';
          echo $language['version_caption'].': '.$GetComApps[$key]['version'].'<br>';
          echo $language['size_caption'].': '.$size.'<br>';
          echo '</span>';
          echo '<div style="padding: 25px 60px;">';

          if($CurrentVersionOS < $GetComApps[$key]['os_version']){
            echo '<div class="AppTile-button A-button-open" style="width: max-content;">';
            echo $language['need_update_button'];
          }else{
            echo '<div update="true" app="'.$GetComApps[$key]['name'].'" app_second="'.UTFTransform($GetComApps[$key]['second_name']).'" hash="'.$GetComApps[$key]['hash'].'" class="AppTile-button A-button-install">';
            echo $language['pub_update_app'];
          }

          echo '</div>';
          echo '</div>';
          echo '</div>';
          echo '</div>';
          echo '<div class="AppTile-description">';
          echo $description;
          echo '</div>';
          echo '</div>';
        }
        unset($newversion, $curversion);
      }
    }

    if($showEmpty){
      echo '<div style="padding: 10px; font-size: 17px; text-align: center; color: #3c3c3c;">'.$language['updates_null'].'</div>';
    }
    ?>
  </div>
</div>


</div>

<?
$AppContainer->EndContainer()
?>

<script>

<?

//Execute Function Request
$AppContainer->ExecuteFunctionRequest();

// Select file Callback
$AppGUI->CallbackSelectFile($AppID);

// Publish App!
$AppContainer->Event(
	"PublishNewApp",
  NULL,
	$Folder,
	'main',
	array(
    'name' => '"+escape($("#name'.$AppID.'").val())+"',
    'second_name' => '"+escape($("#second_name'.$AppID.'").val())+"',
    'version' => '"+escape($("#version'.$AppID.'").val())+"',
    'os_version' => '"+escape($("#os_version'.$AppID.'").val())+"',
    'description' => '"+escape($("#description'.$AppID.'").val())+"',
    'file_url' => '"+escape($("#file_url'.$AppID.'").val())+"',
    'icon_url' => '"+escape($("#icon_url'.$AppID.'").val())+"',
    'activetab' => '"+$("#Tabs'.$AppID.'").tabs(\'option\',\'active\')+"'
	)
);

// LoadPage
$AppContainer->Event(
	"LoadPage",
  'page',
	$Folder,
	'main',
	array(
    'Page' => '"+page+"',
    'activetab' => '"+$("#Tabs'.$AppID.'").tabs(\'option\',\'active\')+"',
    'ScrollTo' => '"+$("#'.$AppName.$AppID.'").scrollTop()+"'
	)
);

// App Search
$AppContainer->Event(
	"AppSearch",
  NULL,
	$Folder,
	'main',
	array(
    'AppSearch' => '"+escape($("#AppSearch'.$AppID.'").val())+"'
	)
);

// Update App!
$AppContainer->Event(
	"UpdateApp",
  NULL,
	$Folder,
	'main',
	array(
    'name' => '"+escape($("#name'.$AppID.'").val())+"',
    'second_name' => '"+escape($("#second_name'.$AppID.'").val())+"',
    'version' => '"+escape($("#version'.$AppID.'").val())+"',
    'os_version' => '"+escape($("#os_version'.$AppID.'").val())+"',
    'description' => '"+escape($("#description'.$AppID.'").val())+"',
    'file_url' => '"+escape($("#file_url'.$AppID.'").val())+"',
    'icon_url' => '"+escape($("#icon_url'.$AppID.'").val())+"',
    'update' => 'true',
    'activetab' => '"+$("#Tabs'.$AppID.'").tabs(\'option\',\'active\')+"'
	)
);

// Select App
$AppContainer->Event(
	"SelectEditApp",
  NULL,
	$Folder,
	'main',
	array(
    'select_edit_app' => '"+escape($("#SelectApp'.$AppID.'").val())+"',
    'activetab' => '"+$("#Tabs'.$AppID.'").tabs(\'option\',\'active\')+"'
	)
);

// Delete App
$AppContainer->Event(
	"DeleteApp",
  NULL,
	$Folder,
	'main',
	array(
    'delete_app' => $_GET['select_edit_app'],
    'activetab' => '"+$("#Tabs'.$AppID.'").tabs(\'option\',\'active\')+"'
	)
);

// Install App!
$AppContainer->Event(
	"InstallApp",
  "AppHash, AppName, SecondName, UpdateMode",
	$Folder,
	"main",
	array(
    'install_app_hash' => '"+AppHash+"',
    'install_app_name' => '"+AppName+"',
    'install_app_second_name' => '"+SecondName+"',
    'install_app_update_mode' => '"+UpdateMode+"',
    'activetab' => '"+$("#Tabs'.$AppID.'").tabs(\'option\',\'active\')+"',
    'ScrollTo' => '"+$("#'.$AppName.$AppID.'").scrollTop()+"',
    'AppSearch' => '"+escape($("#AppSearch'.$AppID.'").val())+"'
	)
);

?>

$('.A-button-open').click(function(){
  makeprocess('system/apps/'+$(this).attr('app')+'/main.php', '', '', $(this).attr('app'));
  closeInfo<?echo $AppID?>($(this).attr('hash'));
});

$('.A-button-install').click(function(){
  InstallApp<?echo $AppID?>($(this).attr('hash'), $(this).attr('app'), $(this).attr('app_second'), $(this).attr('update'));
  closeInfo<?echo $AppID?>($(this).attr('hash'));
});

$(function(){
  $("#Tabs<?echo $AppID?>").tabs();
});

//set active tab
$(function(){
  $("#Tabs<?echo $AppID?>").tabs({
    active: <?echo $activeTab?>
  });
});

function showInfo<?echo $AppID?>(object){
  $(".AppTile-full").css('display', 'none');
  $("#"+object).show("fade", 100);
}

function closeInfo<?echo $AppID?>(object){
  $("#"+object).css('display', 'none');
}

function update<?echo $AppID?>(){
  makeprocess('system/apps/update/main.php','','','Update');
}

function CheckUpdateLabel<?echo $AppID?>(){
   count = <? echo $UpdatesCount ?>;
  if(count > 0){
    $( "a[href='#Updates<?echo $AppID?>" ).html($( "a[href='#Updates<?echo $AppID?>" ).html() +" <span class='AppHouseUpdateCount'>+" + count + "</span>");
  }
}

CheckUpdateLabel<?echo $AppID?>();

//ScrollWindow
$('#<?echo $AppName.$AppID?>').scrollTop(<?echo $ScrollTo?>);

</script>
