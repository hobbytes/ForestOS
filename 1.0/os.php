<!--
   ____                 __    ____  ____
  / __/__  _______ ___ / /_  / __ \/ __/
 / _// _ \/ __/ -_|_-</ __/ / /_/ /\ \
/_/  \___/_/  \__/___/\__/  \____/___/
          WET STONE 1.0
-->
<?
include 'system/core/library/gui.php';
include 'system/core/library/bd.php';
include 'system/core/library/auth.php';
include 'system/core/library/etc.php';
include 'system/core/library/filesystem.php';
include 'system/core/library/prepare.php';
$object = new gui;
$infob = new info;
$hashfile = new fileaction;
$prepare = new prepare;
$auth = new AuthClassUser();
session_start();
$auth->checkout();
$prepare->start();
$prepare->wall();
$prepare->themeload();
?>

<body class="ui-body backgroundtheme" style="position:relative; z-index:-2000; overflow-x:hidden; overflow-y:hidden; background: url(<?echo $mainwall;?>) 100% 100% no-repeat fixed; background-size:cover;">
<?
if(isset($_SESSION['loginuser'])){
?>
<div class="welcomescreen" style="position:absolute; width:100%; height:100%; top:0; left:0; background-image: linear-gradient(180deg, #051f27, #153333);text-align:center; color:#fff;"><div class="spinner"></div></div>
<div id="topbar" class="ui-widget-content topbartheme" style="display:none; z-index:9999; height:22px; padding-top:4px;">
  <span id="hideall" class="topbaractbtn" style="cursor:pointer; display:none; background-color:#5ca556; color:#fff; width:12px; float:right; text-align:center; width:15px; margin-right: 8px;">-</span>
  <span id="closeall" class="topbaractbtn" style="cursor:pointer; display:none; background-color:#bf5a5a; color:#fff; float:right; text-align:center; width:15px;" onclick="$('.process').remove(); $('.topbaractbtn').css('display','none');">x</span>
  <div class="date" style="float:right; font-size:15px; padding-right:10px; user-select: none; cursor: default;">
      <?php echo $object->getDayRus().' '.date('d').',';?>
    <span id="time"></span>
      </div>
      <div id="notificationsbtn" style="float:right; font-size: 11px; margin-right: 10px; padding: 1px; user-select: none; border: 2px solid #fff; border-radius: 4px; cursor: default;">N</div>
  <script type="text/javascript">showTime();</script>
  <div id="menu1" onmouseover="document.getElementById('aboutmenu').style.display='block';" onmouseout="document.getElementById('aboutmenu').style.display='none';" style="z-index:9999; user-select: none; cursor: default; text-align:center; width:50px; font-size:19px; ">=</div>
</div>
<div id="aboutmenu" class="ui-widget-content menutheme" onmouseover="document.getElementById('aboutmenu').style.display='block';" onmouseout="document.getElementById('aboutmenu').style.display='none';"  style="z-index:9999; user-select:none; display:none; text-align:justify; width:150px; max-width:300px; position:absolute; text-overflow:hidden; overflow:ellipsis; padding:5px;">
<span style="text-transform:uppercase; cursor:pointer;" onclick="makeprocess('Settings','users','<?echo $login;?>','selectuser');"><?echo $login;?></span>
<hr><span style="cursor:pointer;" onclick="makeprocess('Explorer','main','',''); document.getElementById('aboutmenu').style.display='none';">Проводник</span>
<hr><span style="cursor:pointer;" onclick="makeprocess('Settings','main','',''); document.getElementById('aboutmenu').style.display='none';">Параметры</span>
<hr><span style="cursor:pointer;" onclick="makeprocess('Apps_House','main','',''); document.getElementById('aboutmenu').style.display='none';">Магазин</span>
<hr><span style="cursor:pointer;" onclick="makeprocess('Settings','about','',''); document.getElementById('aboutmenu').style.display='none';">О системе</span>
<hr><b><span style="cursor:pointer;" onclick="return location.href = 'os.php'">Перезагрузка</span></b>
<hr><b><span style="cursor:pointer;" onclick="return location.href = '?action=logout'">Выйти</span></b>
</div>

<div id="desktop">
<?
$count=0;
foreach (glob("system/users/$login/desktop/*.link") as $filename)
{
  $prepare->desktop($count=$count+1,$filename,"refdiv");
}
$_SESSION['appid']=-1;
?>
<div id="notifications" class="notificationhide" style="display:block; position:absolute; right: 0; height: 100%; padding: 10px; transition:all 0.2s ease;">
</div>
</div>
<script>
var id=<?echo $_SESSION['appid']=$_SESSION['appid']+1?>;
function checkwindows(){closestyle="";var prc=$(".process").length;if (prc>1){closestyle="inline";}else{closestyle="none";} $(".topbaractbtn").css('display',''+window.closestyle+'');}
function makeprocess(dest,file,param,key){
$('.ui-body').append("<div id=\"process"+(id=id+1)+"\" class='process' style='display:none;'></div>"); $("#process"+id+"").show('drop',500); $("#process"+id+"" ).load("makeprocess.php?id=<?echo md5(rand(0,10000).date('d.m.y.h.i.s'));?>"+id+"&d=system/apps/"+dest+"/&i="+id+"&n="+dest+"&p="+param+"&f="+file+"&k="+key+"");
checkwindows();};

function makeprocess2(dest,param,key){
$('.ui-body').append("<div id=\"process"+(id=id+1)+"\" class='process' style='display:none;'></div>"); $("#process"+id+"").show('drop',500); $("#process"+id+"" ).load("makeprocess2.php?id=<?echo md5(rand(0,10000).date('d.m.y.h.i.s'));?>"+id+"&d="+dest+"/&i="+id+"&p="+param+"&k="+key+"");
checkwindows();};
$( function() {
$( "#notificationsbtn" ).on( "click", function() {
  $('.notificationclass').css('opacity','0');
  if($( "#notifications" ).hasClass("notificationshow"))
  {
    $('.notificationclass').css('opacity','0');
  }
  else
  {
    $('.notificationclass').css('opacity','0.97');
  }
  $( ".notificationhide" ).toggleClass( "notificationshow", 100 );
});
$( "#topbar" ).on( "dblclick", function() { $( ".blurwindowpassive" ).toggleClass( "blurwindowactive", 100 );});
function runEffect() {
var options = {};
$( ".hideallclass" ).toggle( "slide", options, 100 );};
$( "#hideall" ).on( "click", function() {runEffect(); $( ".dragwindow" ).toggleClass( "dragwindowtoggle", 500 );});
  } );
  $( function() {
    $(window).load(function(){$(".welcomescreen").hide('fade',500);
    $("#topbar").show('fade',1500);
    $("#topbar").css('display','block')});
    $( ".ico" ).draggable({containment:"body", snap:".ico, #topbar"});
    $( ".window" ).mouseup(function(){$(".window").removeClass("windowactive")});
  });
</script>
</body>
</html>
<?$_SESSION['appid']='<script>document.writeln(id)</script>';
}else{
include 'login.php';
}

?>
