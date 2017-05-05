<?
session_start();
if(isset($_SESSION['loginuser'])){
include 'system/core/library/etc.php';
include 'system/core/library/gui.php';
$infob = new info;
$object = new gui;
//определяем мобильное устройство
$infob->ismobile();
if($mobile=='true'){$click='click'; $autohide='false'; $top='20px';$left='0px'; $maxwidthm='1'; $maxwidth='100%';}else{$click='dblclick'; $autohide='true'; $top='25%';$left='25%'; $maxwidth='95%'; $maxwidthm='0.95';}
$d=$_GET['d'];$i=$_GET['i'];$p=$_GET['p'];$k=$_GET['k'];

function makeprocess($destination,$idprocess,$param,$key){
global $click,$top,$left,$maxwidth,$autohide,$object,$maxwidthm;
$folder=dirname($destination);$folder=stristr($folder, 'system/');$destination=stristr($destination, 'system/');
$appicon=$folder.'/app.png';
if (!is_file($appicon)){
  $appicon='system/core/design/images/app.png';
}
$pname=basename($destination);
$style="'display','block'";
$style2="'display','none'";
echo '<div id=app'.$idprocess.' style="max-width:'.$maxwidth.'; position:absolute; left:'.$left.'; top:'.$top.';" class="ui-widget-content window windownormal" ><div id=drag'.$idprocess.' class="ui-widget-header dragwindow"><span  style="cursor:default;"><div style="background-color:transparent;  background-image: url('.$appicon.'); background-size:cover; height:20px; width:20px; margin:0px 3px 0px 3px; float:left;"></div>'.$pname.' </span><span class="appwindowbutton"'; echo ' onClick=" $(';echo "'#"; echo 'process'.$idprocess; echo "'"; echo ').remove();';echo ' $(';echo "'#"; echo "app$idprocess"; echo "'"; echo ').css('; echo "$style2"; echo '); checkwindows();" style="
background-color:#ed2020;">x</span><span class="hidewindow'.$idprocess.' appwindowbutton"'; echo ' onClick="" style="
background-color:#37a22e;">-</span></div><div id='.$idprocess.' class="blurwindowpassive hideallclass" ></div></div></div>';
?><div id="logic<?echo $idprocess;?>">
  <script async>
  $( function() {
    $( "#<?echo 'app'.$idprocess.'';?>" ).draggable({containment:"body",handle:"<?echo '#drag'.$idprocess.'';?>", snap:".ui-body, .dragwindowtoggle, #topbar"});
    $( "#<?echo 'app'.$idprocess.'';?>" ).resizable({containment:"body",minHeight:$(window).height()*0.15,minWidth:$(window).width()*0.15,maxWidth:$(window).width()*<?echo $maxwidthm;?>,maxHeight:$(window).height()*0.95,autoHide:<?echo $autohide;?>,alsoResize:"#<?echo ''.$nameprocess.$idprocess.'';?>"});
    $( "#<?echo 'app'.$idprocess.'';?>" ).click(function(){$("#<?echo 'app'.$idprocess.'';?>" ).addClass("windowactive")});
    $( "#<?echo 'drag'.$idprocess.'';?>" ).click(function(){$("#<?echo 'app'.$idprocess.'';?>" ).addClass("windowactive")});
    $( "#<?echo 'drag'.$idprocess.'';?>" ).dblclick(function(){$("#<?echo 'app'.$idprocess.'';?>" ).css({top:"21px",left:"0"})});
    $( ".window" ).mouseup(function(){$(".window").removeClass("windowactive")});
    $("#<?echo $idprocess;?>" ).load("<? echo $destination.'?id='.rand(0,10000).'&appid='.$idprocess.'&appname='.$pname.'&destination='.$folder.'&mobile='.$click.'&'.$key.'='.$param;?>");
  $(function() {$(".window").removeClass("windowactive");$("#<?echo 'app'.$idprocess.'';?>" ).addClass("windowactive")});
  function runEffect() {
  var options = {};
  $( "#<?echo $idprocess;?>" ).toggle( "slide", options, 100 );};
  $( ".hidewindow<?echo $idprocess;?>" ).on( "click", function() {runEffect(); $( "#<?echo 'drag'.$idprocess;?>" ).toggleClass( "dragwindowtoggle", 500 );});
  $( "#<?echo 'drag'.$idprocess.'';?>" ).on( "dblclick", function() { $( "#<?echo 'app'.$idprocess.'';?>" ).toggleClass( "windowfullscreen", 100 );});


    } );
  </script>
  </div>
  <?
}
makeprocess($d,$i,$p,$k);}
else{header('Location: os.php'); exit;}
?>
