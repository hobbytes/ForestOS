<!--
   ____                 __    ____  ____
  / __/__  _______ ___ / /_  / __ \/ __/
 / _// _ \/ __/ -_|_-</ __/ / /_/ /\ \
/_/  \___/_/  \__/___/\__/  \____/___/
          WET STONE 1.0
-->
<?
require 'system/core/library/gui.php';
require 'system/core/library/bd.php';
require 'system/core/library/auth.php';
require 'system/core/library/etc.php';
require 'system/core/library/filesystem.php';
require 'system/core/library/prepare.php';
session_start();
$object = new gui;
$infob = new info;
$hashfile = new fileaction;
$prepare = new prepare;
$auth = new AuthClassUser();
$auth->checkout();
$prepare->start();
$prepare->wall();
$prepare->themeload();
?>
<body class="ui-body backgroundtheme" style="position:relative; z-index:-2000; overflow-x:hidden; overflow-y:hidden; background: url(<?echo $mainwall;?>) 100% 100% no-repeat fixed; background-size:cover;">
<?
if(isset($_SESSION['loginuser'])){
$prepare->welcomescreen();
$prepare->topbar();
?>
<div id="desktop">
<?
$prepare->desktop("linkdiv");
$_SESSION['appid']=-1;
?>
<div id="notifications" class="notificationhide" style="display:block; position:absolute; right: 0; height: 100%; padding: 10px; transition:all 0.2s ease;">
</div>
</div>
<div id="proceses">
  <?
  $prepare->hibernation();
  ?>
</div>
</body>
</html>
<script>
var id=<?echo $_SESSION['appid']=$_SESSION['appid']+1?>;

function  hibernation(logout){
  $('.process').addClass('hibernatethis');
  var savestate = ($('#proceses').html());
  $.ajax({
    type: "POST",
    url: "system/core/library/etc/hibernation",
    data: {
       content:savestate,
       appid:id
    }
  }).done(function(o) {
    if(logout == 'true'){
      return location.href = '?action=logout';
    }else{
      return location.href = 'os.php';
    }
});
}
function checkwindows(){
  closestyle="";
  var prc=$(".process").length;
  if (prc>1){
    closestyle="inline";
  }else{
    closestyle="none";
  }
  $(".topbaractbtn").css('display',''+window.closestyle+'');
}

function makeprocess(dest,file,param,key){
  $('.ui-body').append("<div id=\"process"+(id=id+1)+"\" class='process' style='display:none;'></div>");
  $("#process"+id+"").show('drop',500);
  $("#process"+id+"" ).load("makeprocess.php?id=<?echo md5(rand(0,10000).date('d.m.y.h.i.s'));?>"+id+"&d=system/apps/"+dest+"/&i="+id+"&n="+dest+"&p="+param+"&f="+file+"&k="+key+"");
  checkwindows();
};

function makeprocess2(dest,param,key){
  $('.ui-body').append("<div id=\"process"+(id=id+1)+"\" class='process' style='display:none;'></div>"); $("#process"+id+"").show('drop',500);
  $("#process"+id+"" ).load("makeprocess2.php?id=<?echo md5(rand(0,10000).date('d.m.y.h.i.s'));?>"+id+"&d="+dest+"/&i="+id+"&p="+param+"&k="+key+"");
  checkwindows();
};

$( function() {
  $( "#notificationsbtn" ).on( "click", function() {
    $('.notificationclass').css('opacity','0');
    if($( "#notifications" ).hasClass("notificationshow"))
    {
      $('.notificationclass').css('opacity','0');
      $('.notificationclass').css('display','none');
    }else{
      $('.notificationclass').css('opacity','0.97');
      $('.notificationclass').css('display','block');
    }
    $( ".notificationhide" ).toggleClass( "notificationshow", 100 );
  });
$( "#topbar" ).on( "dblclick", function() {
  $( ".blurwindowpassive" ).toggleClass( "blurwindowactive", 100 );
});

function runEffect() {
  var options = {};
  $( ".hideallclass" ).toggle( "slide", options, 100 );
};
$( "#hideall" ).on( "click", function() {
  runEffect();
  $( ".dragwindow" ).toggleClass( "dragwindowtoggle", 500 );
});
});

  $( function() {
    $(window).load(function(){
      $(".welcomescreen").hide('fade',500);
      $("#topbar").show('fade',1500);
      $("#topbar").css('display','block')
    });
    $( ".ico" ).draggable({containment:"body", snap:".ico, #topbar"});
    $( ".window" ).mouseup(function(){
      $(".window").removeClass("windowactive")
    });
  });
</script>
<?
$_SESSION['appid']  = '<script>document.writeln(id)</script>';
$prepare->autorun();
}else{
include 'login.php';
}
?>
