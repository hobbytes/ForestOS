<?
/**
 * Mercury library for Forest OS
 * ==============================
 * Version: 1.1
 * Author: Vyacheslav Gorodilov
**/

class  AppContainer {

  /* App Info */
  public $AppNameInfo = 'App Name'; // app name information @string
  public $SecondNameInfo = 'Second Name'; // second app name information @string
  public $VersionInfo = '1.0'; // app version @string
  public $AuthorInfo = 'Author'; // app version @string

  /* Library Array */
  public $LibraryArray = array(); // get libraries @array

  /* Container Info */
  public $appName; // app container name @string
  public $appID; // app container ID @integer
  public $backgroundColor = '#f7f7f7'; // custom background-color
  public $fontColor = '#000'; // custom font color
  public $height = '550px'; // app container height @string
  public $width = '800px'; // app container width @string
  public $customStyle = NULL; // custom CSS style @string
  public $isMobile = NULL; // which device style @string
  public $securityMode = true; // use security fucntion @boolean
  public $showError = false; // error display @boolean
  public $showStatistics = false; // statistics display @boolean


/* start container */
  public function StartContainer(){

    //start session
    if(!isset($_SESSION)){
      session_start();
    }

    //start timer for stats
    if($this->showStatistics){
      echo '
      <script>
        var timerStart = performance.now();
      </script>
      ';
    }

    //check error state
    if($this->showError){
      ini_set('display_errors','On');
      error_reporting(E_ALL);
    }

    //set timezone
    $timezone = $_SESSION['timezone'];
    if (function_exists('date_default_timezone_set')){
      date_default_timezone_set("$timezone");
    }

    //set Application Information
    if(isset($_GET['getinfo']) && $_GET['getinfo'] == 'true'){
      $arrayInfo = array(
        'name' => $this->AppNameInfo, // set app name
        'version' => $this->VersionInfo, // set version
        'author' => $this->AuthorInfo, // set author name
        'secondname' => $this->SecondNameInfo // set second app name
      );
      print_r(json_encode($arrayInfo)); // show information
      die();
    }

    // check security
    if($this->securityMode){
      global $security;
      require_once $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
      $security	=	new security;
      $security->appprepare();
    }

    // find libraries
    if(!empty($this->LibraryArray)){
      $LibRoot = $_SERVER['DOCUMENT_ROOT'].'/system/core/library';
      $array = $this->LibraryArray;
      $LostLibs = array(); // array for lost libs
      foreach ($array as $key) {
        foreach (glob($LibRoot . "{/*/,/}" . $key . ".php", GLOB_BRACE) as $filename) {
          if(is_file($filename)){
            require $filename;
          }else{
            $LostLibs[] = $key;  // collect lost libs
          }
        }
      }

      if(!empty($LostLibs)){  // show all lost libs and die...
        echo '<div style="background: #fff; color: #000; padding: 10px;">';
        echo "Warning!<br><br>";
        foreach ($LostLibs as $object) {
          echo "This library was not found: <b>$object</b><br>";
        }
        echo '</div>';
        die();
      }
    }

    // check var and make new container
    if($this->appName && $this->appID){
      echo '<div id="'.$this->appName.$this->appID.'" style="background-color:'.$this->backgroundColor.'; color:'.$this->fontColor.'; height:'.$this->height.'; width:'.$this->width.'; max-height:96%; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto; '.$this->customStyle.'">';
    }

  }

/* end container + JS Function for resize window */
  public function EndContainer(){

    //statistics container
    if($this->showStatistics){
      echo '<div id="statistics-'.$this->appID.'" class="stat-container"></div>';
    }

    if($this->appName && $this->appID){
      echo '<script>';
      echo 'UpdateWindow("'.$this->appID.'","'.$this->appName.'");';

      //update app length
      echo '$("#app'.$this->appID.'").attr("applength-'.$this->appID.'", parseInt($("#app'.$this->appID.'").attr("applength-'.$this->appID.'")) + $("#'.$this->appName.$this->appID.'").html().length);';

      //show statistics data
      if($this->showStatistics){
        echo '
        let pagebytes = $("#'.$this->appName.$this->appID.'").html().length;
        let readyTime = (performance.now() - timerStart).toPrecision(3);
        $("#statistics-'.$this->appID.'").append("Load time: "+ readyTime + " ms.<br>");
        $("#statistics-'.$this->appID.'").append("Size: "+ bytesToSize(pagebytes) +".<br>");
        $("#statistics-'.$this->appID.'").append("App traffic: "+ bytesToSize($("#app'.$this->appID.'").attr("applength-'.$this->appID.'")));
        ';
      }

      echo '</script>';

      //close container
      echo '</div>';
    }
  }

  /* Get any requets */
  public function GetAnyRequest($request, $default = NULL){

    if(isset($_GET[$request])){

  		return $_GET[$request];

  	}elseif (isset($_POST[$request])){

  		return $_POST[$request];

  	}else{

      return $default;

    }

  }

  /* Event function */
  public function Event($FunctionName, $Argument = NULL, $Folder, $File, $RequestData = array(), $CustomFunction = NULL, $CustomFunctionMode = 1, $CustomContainer = NULL){

    /**
     @param string $FunctionName
     @param string $Argument
     @param string $Folder
     @param string $File
     @param array $RequestData
     @param string $CustomFunction
     @param string $CustomFunctionMode
     @param string $CustomContainer
     */

    /* Requset Data buffer*/
    $_RequestData = NULL;

    /* parse array */
    if(!empty($RequestData)){
      foreach ($RequestData as $key => $value) {
        $_RequestData = $_RequestData.'&'.$key.'='.$value;
      }
    }

    /* custom container */
    $ContainerName = $this->appID;

    if(!empty($CustomContainer)){
      $ContainerName = $CustomContainer;
    }

    /* is Mobile? */
    $_isMobile = NULL;

    if(!empty($this->isMobile)){
      $_isMobile = '&mobile='.$this->isMobile;
    }

    /* print function (dirty code...)*/
    echo '
    /* function '.$FunctionName.$this->appID.' */
    function '.$FunctionName.$this->appID.'('.$Argument.'){
      ';

    /* print custom function */
    if(!empty($CustomFunction && $CustomFunctionMode == 0)){
      echo "$CustomFunction\r\n";
    }

    echo '$("#'.$ContainerName.'").load("'.$Folder.$File.'.php?id='.rand(0,10000).'&destination='.$Folder.'&appname='.$this->appName.'&appid='.$this->appID.$_RequestData.$_isMobile.'");';

    /* print custom function */
    if(!empty($CustomFunction && $CustomFunctionMode == 1)){
      echo "\r\n$CustomFunction";
    }

    /* close function */
    echo "\r\n};\r\n";

  }

/* Event request function */
  public function ExecuteFunctionRequest(){

    echo '/* function ExecuteFunctionRequest'.$this->appID.' */';
    echo 'function ExecuteFunctionRequest'.$this->appID.'( ObjectName, FunctionName, FunctionArgument = null ){';
      echo 'if(Array.isArray(FunctionArgument)){';
        echo 'FunctionArgument = FunctionArgument.toString().replace(",","\',\'");';
      echo '}';
      echo 'if(!$("#RequestBox'.$this->appID.'").length){ FunctionArgument = "\'"+FunctionArgument+"\'"; $("#'.$this->appName.$this->appID.'").append("<div id=\"RequestBox'.$this->appID.'\" class=\"forest-ui-request-box\"><div class=\"forest-ui-request-box-description\">"+$(ObjectName).attr(\'messageTitle\')+"<div>"+$(ObjectName).attr(\'messageBody\')+"</div></div><div class=\"forest-ui-request-box-button-container\"><div class=\"forest-ui-request-box-button forest-ui-request-box-button-ok\" onClick=\"eval("+FunctionName+\'(\'+FunctionArgument+\')\'+"); hidebox'.$this->appID.'(); \">"+$(ObjectName).attr(\'okButton\')+"</div><div class=\"forest-ui-request-box-button forest-ui-request-box-button-cancel\" onClick=\"hidebox'.$this->appID.'()\">"+$(ObjectName).attr(\'cancelButton\')+"</div></div> <script> function hidebox'.$this->appID.'(){$(\'#RequestBox'.$this->appID.'\').slideUp(\'fast\', function(){$(\'#RequestBox'.$this->appID.'\').remove()}); } <\/script> </div>");}';
      echo '
      function ShowCloseRequset'.$this->appID.'(){
        if($("#RequestBox'.$this->appID.'").is( ":hidden" )){
          $("#RequestBox'.$this->appID.'").slideDown("fast");
        }else{
          $("#RequestBox'.$this->appID.'").slideUp("fast", function(){
            $("#RequestBox'.$this->appID.'").remove();
          });
        }
      }';
    echo 'ShowCloseRequset'.$this->appID.'();}';

   }

}
?>
