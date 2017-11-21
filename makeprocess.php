<?
session_start();
if(isset($_SESSION['loginuser'])){

  include 'system/core/library/etc.php';
  include 'system/core/library/gui.php';
  $infob = new info;
  $object = new gui;
  //определяем мобильное устройство
  $infob->ismobile();
  if($mobile=='true'){
    $click='click';
    $autohide='false';
    $top='30px';
    $left='0px';
    $maxwidthm='1';
    $maxwidth='100%';
  }else{
    $click='dblclick';
    $autohide='true';
    $top='2'.rand(4,9).'%';
    $left='2'.rand(4,9).'%';
    $maxwidth='95%';
    $maxwidthm='0.95';
  }

  $d=$_GET['d'];//  destination
  $i=$_GET['i'];// id process
  $p=$_GET['p'];// name of property
  $k=$_GET['k'];// key property
  $n=$_GET['n'];// name of process

  function makeprocess($destination,  $idprocess, $param, $key, $name){
  global $click,$top,$left,$maxwidth,$autohide,$object,$maxwidthm;
  $folder=dirname($destination);
  $folder=stristr($folder, 'system/');
  $destination=stristr($destination, 'system/');
  $appicon=$folder.'/app.png';

  if (!is_file($appicon)){
    $appicon='system/core/design/images/app.png';
  }

  if(empty($name)){
    $name=basename($destination);
  }
  $destination_ =  $destination.$file;
  if(!preg_match('/.php/',$destination_)){
     $destination_ = $destination_.'.php';
  }

  $style="'display','block'";
  $style2="'display','none'";
  echo '<div id=app'.$idprocess.' style="max-width:'.$maxwidth.'; position:absolute; left:'.$left.'; top:'.$top.';" class="ui-widget-content window windownormal windowborder">
  <div id=drag'.$idprocess.' class="ui-widget-header dragwindow">
  <span class="process-title" style="cursor:default; font-size:17px;"><div style="background-color:transparent;  background-image: url('.$appicon.'); background-size:cover; height:20px; width:20px; margin:0px 3px 0px 3px; float:left;"></div>'.str_replace('_',  ' ',  $name).' </span>
  <div class="appwindowbutton"'; echo ' onClick=" $(';echo "'#"; echo 'process'.$idprocess; echo "'"; echo ').remove();';echo ' $(';echo "'#"; echo "app$idprocess"; echo "'"; echo ').css('; echo "$style2"; echo '); checkwindows();" style="background-color:#ed2020;">x</div>
  <div class="hidewindow'.$idprocess.' appwindowbutton" onClick="" style="background-color:#37a22e;">-</div>
  <div class="reload'.$idprocess.' appwindowbutton" onClick="" style="background-color:#e09100;">o</div>
  </div><div id='.$idprocess.' class="blurwindowpassive hideallclass process-container" location="'.$destination_.'"></div></div></div>';
  ?>
  <div id="logic<?echo $idprocess;?>">
    <script async>
    $( function() {
      $( "#<?echo 'app'.$idprocess.'';?>" ).draggable({containment:"body",handle:"<?echo '#drag'.$idprocess.'';?>", snap:".ui-body, .dragwindowtoggle, #topbar"});
      $( "#<?echo 'app'.$idprocess.'';?>" ).resizable({containment:"body",minHeight:$(window).height()*0.15,minWidth:$(window).width()*0.15,maxWidth:$(window).width()*<?echo $maxwidthm;?>,maxHeight:$(window).height()*0.95,autoHide:<?echo $autohide;?>,alsoResize:"#<?echo $name.$idprocess?>"});
      $( "#<?echo 'app'.$idprocess.'';?>" ).click(function(){$("#<?echo 'app'.$idprocess.'';?>" ).addClass("windowactive")});
      $( "#<?echo 'drag'.$idprocess.'';?>" ).click(function(){$("#<?echo 'app'.$idprocess.'';?>" ).addClass("windowactive")});
      $( "#<?echo 'drag'.$idprocess.'';?>" ).dblclick(function(){
        $("#<?echo 'app'.$idprocess.'';?>" ).css({
          top:"29px",left:"0"
        })
      });
      $( ".window" ).mouseup(function(){$(".window").removeClass("windowactive")});
      if(!$("#process<?echo $idprocess;?>").hasClass('hibernatethis')){
        $("#<?echo $idprocess;?>" ).load("<? echo $destination.'?id='.rand(0,10000).'&appid='.$idprocess.'&appname='.$name.'&destination='.$folder.'/&mobile='.$click.'&'.$key.'='.$param;?>");
      }
    $(function() {
      $(".window").removeClass("windowactive");
      $("#<?echo 'app'.$idprocess.'';?>" ).addClass("windowactive");
    });
    function runEffect() {
      var options = {};
      $( "#<?echo $idprocess;?>" ).toggle( "slide", options, 100 );
  };
    $( ".hidewindow<?echo $idprocess;?>" ).on( "click", function() {
      runEffect();
      if($( "#<?echo 'app'.$idprocess.'';?>" ).hasClass("ui-resizable")){
          $( "#<?echo 'app'.$idprocess.'';?>" ).resizable({disabled:true,containment:"body"});
        }
        if($( "#<?echo 'app'.$idprocess.'';?>" ).hasClass("windowborderhide")){
          $( "#<?echo 'app'.$idprocess.'';?>" ).resizable({disabled:false,containment:"body",minHeight:$(window).height()*0.15,minWidth:$(window).width()*0.15,maxWidth:$(window).width()*<?echo $maxwidthm;?>,maxHeight:$(window).height()*0.95,autoHide:<?echo $autohide;?>,alsoResize:"#<?echo ''.$nameprocess.$idprocess.'';?>"});
        }
      $( "#<?echo 'drag'.$idprocess;?>" ).toggleClass( "dragwindowtoggle", 500 );
      $( "#<?echo 'app'.$idprocess;?>" ).toggleClass( "windowborderhide", 500 );
      $( "#<?echo 'app'.$idprocess;?>" ).toggleClass( "bordertoggle", 1 );
    });

    $(".reload<?echo $idprocess;?>" ).on( "click", function() {
      $("#<?echo $idprocess;?>" ).load("<? echo $destination_.'?id='.rand(0,10000).'&appid='.$idprocess.'&appname='.$nameprocess.'&destination='.$folder.'/&mobile='.$click.'&'.$key.'='.$param;?>");
    });
    $("#<?echo 'drag'.$idprocess?>" ).on( "dblclick", function() {
       $("#<?echo 'app'.$idprocess?>" ).toggleClass( "windowfullscreen", 100 );
     });
    $("#process<?echo $idprocess;?>" ).appendTo("#proceses");
      });
    </script>
    </div>
    <?
  }
  makeprocess($d, $i, $p, $k, $n);
}else{
  header('Location: os.php');
  exit;
}
?>
