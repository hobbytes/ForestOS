<?
/**
 * Mercury library for Forest OS
 * ==============================
 * Author: Vyacheslav Gorodilov
 */

class  AppContainer {

  /* App Info */
  public $AppNameInfo = 'App Name'; // app name information @string
  public $SecondNameInfo = 'Second Name'; // second app name information @string
  public $VersionInfo = '1.0'; // app version @string
  public $AuthorInfo = 'Author'; // app version @string

  /* Library List */
  public $LibraryArray = array(); // get libraries @array

  /* Container Info */
  public $appName; // app container name @string
  public $appID; // app container ID @integer
  public $backgroundColor = '#f2f2f2'; // custom background-color
  public $fontColor = '#000'; // custom font color
  public $height = '550px'; // app container height @string
  public $width = '800px'; // app container width @string
  public $customStyle = NULL; // custom CSS style @string
  public $isMobile = NULL; // which device style @string
  public $showError = false; // error display @boolean
  public $showStatistics = false; // statistics display @boolean

/* start container */
  public function StartContainer(){

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
    global $security;
    require $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/security.php';
    $security	=	new security;
    $security->appprepare();

    // find libraries
    if(!empty($this->LibraryArray)){
      $array = $this->LibraryArray;
      foreach ($array as $key) {
        $key = $key.'.php'; // library file
        $FirstFindPlace = $_SERVER['DOCUMENT_ROOT'].'/system/core/library/'.$key; // First find place
        $SecondFindPlace = $_SERVER['DOCUMENT_ROOT'].'/system/core/library/etc/'.$key; // Second find place
        $ThirdFindPlace = $_SERVER['DOCUMENT_ROOT'].'/system/core/library/Mercury/'.$key; // Third find place
        if(is_file($FirstFindPlace)){ // if find then require this!
          require $FirstFindPlace;
        }
        elseif(is_file($SecondFindPlace)){ // if find then require this!
          require $SecondFindPlace;
        }
        elseif(is_file($ThirdFindPlace)){ // if find then require this!
          require $ThirdFindPlace;
        }
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
        $("#statistics-'.$this->appID.'").append("Size: "+ (pagebytes / 1024).toPrecision(3) + " kb.<br>");
        $("#statistics-'.$this->appID.'").append("App traffic: "+ ($("#app'.$this->appID.'").attr("applength-'.$this->appID.'") / 1024).toPrecision(3) + " kb.");
        ';
      }

      echo '</script>';

      //close container
      echo '</div>';
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

}
?>
