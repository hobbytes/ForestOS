
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
    }
    if(logout == 'false'){
      return location.href = 'os.php';
    }
});
}

function checkwindows(){
  closestyle  = "";
  var prc = $(".process").length;
  if (prc > 1){
    closestyle  = "inline";
  }else{
    closestyle  = "none";
  }
  $(".topbaractbtn").css('display',''+window.closestyle+'');
}

function makeprocess(dest,  param,  key,  name){
  $('.ui-body').append("<div id=\"process"+(id=id+1)+"\" class='process' style='display:none;'></div>");
  $("#process"+id+"" ).load("makeprocess.php?id=<?echo md5(date('d.m.y.h.i.s'));?>"+id+"&d="+dest+"/&i="+id+"&p="+param+"&k="+key+"&n="+name);
  $("#process"+id+"").show('drop', 500);
  checkwindows();
};

function ProcessLogic(id, name, destination, destination_, maxwidthm, folder, click, key, param, autohide){
  $("#app" + id).draggable({containment:"body",handle:"#drag" + id, snap:".ui-body, .dragwindowtoggle, #topbar"});
  $("#app" + id).resizable({containment:"body",minHeight:$(window).height()*0.15,minWidth:$(window).width()*0.15,maxWidth:$(window).width()*maxwidthm,maxHeight:$(window).height()*0.95,autoHide:autohide,alsoResize:"#"+name+id});
  $("#app" + id).click(function(){$("#app" + id ).addClass("windowactive")});
  $( "#drag" + id ).click(function(){$("#app" + id ).addClass("windowactive")});
  $( "#drag" + id ).dblclick(function(){$("#app" + id ).css({top:"29px",left:"0"});});
  $( ".window" ).mouseup(function(){$(".window").removeClass("windowactive")});
  if(!$("#process" + id).hasClass('hibernatethis')){
    $("#" + id).load(""+destination+"?id=<?echo rand(0,10000)?>&appid="+id+"&appname="+name+"&destination="+folder+"/&mobile="+click+"&"+key+"="+param);
  }
  $(function() {
    $(".window").removeClass("windowactive");
    $("#app" + id ).addClass("windowactive");
  });
  function runEffect() {
    var options = {};
    $( "#" + id).toggle( "slide", options, 100 );
  };
  $( ".hidewindow" + id ).on( "click", function() {
    runEffect();
    if($("#app" + id).hasClass("ui-resizable")){
        $("#app" + id).resizable({disabled:true,containment:"body"});
      }
      if($("#app" + id).hasClass("windowborderhide")){
        $("#app" + id).resizable({disabled:false,containment:"body",minHeight:$(window).height()*0.15,minWidth:$(window).width()*0.15,maxWidth:$(window).width()*maxwidthm,maxHeight:$(window).height()*0.95,autoHide:autohide,alsoResize:"#"+name+id});
      }
      $( "#drag" + id ).toggleClass( "dragwindowtoggle", 500 );
      $("#app" + id).toggleClass( "windowborderhide", 500 );
      $("#app" + id).toggleClass( "bordertoggle", 1 );
    });
  $(".reload" + id).on( "click", function() {
    $("#" + id).load(""+destination_+"?id=<?echo rand(0,10000)?>&appid="+id+"&appname="+name+"&destination="+folder+"/&mobile="+click+"&"+key+"="+param);
  });
  $("#app" + id).resize(function(){
      $("#app" + id).attr("ww", $("#"+name+id).css("width"));
      $("#app" + id).attr("wh", $("#"+name+id).css("height"));
  });
  $("#drag" + id ).on( "dblclick", function() {
    if(!$("#app" + id).hasClass("windowfullscreen")){
      $("#app" + id).attr("ww", $("#"+name+id).css("width"));
      $("#app" + id).attr("wh", $("#"+name+id).css("height"));
      $("#app" + id).css("width","");
      $("#app" + id).css("height","");
    }
    if($("#app" + id).hasClass("windowfullscreen")){
      $("#"+name+id).css("width",$("#app" + id).attr("ww"));
      $("#"+name+id).css("height",$("#app" + id).attr("wh"));
      $("#app" + id).css("top","25%");
      $("#app" + id).css("left","25%");
    }
    $("#app" + id ).toggleClass( "windowfullscreen", 100, function(){
        UpdateWindow(id,name);
      });
    });
  $("#process" + id ).appendTo("#proceses");
};

$( function() {
  $( "#notificationsbtn" ).on( "click", function() {
    $("#notificationsbtn").css({'border':'2px solid #fff','background-color':'rgba(0,0,0,0)'});
    $('.notificationclass').css('opacity','0');
    $("#notification-container").css('display','block');
    if($( "#notifications" ).hasClass("notificationshow"))
    {
      $('.notificationclass').css({'opacity':'0','display':'none'});
      $('#notificationTopLabel').css('display','none');
    }else{
      $('.notificationclass').css({'opacity':'0.97','display':'block'});
      $('#notificationTopLabel').css('display','block');
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
  $( ".windowborder" ).toggleClass( "windowborderhide", 500 );
  $( ".windowborder" ).toggleClass( "bordertoggle", 1 );
});
});

function releaselink(){
  $(".linktheme").css({
    'white-space' : 'nowrap',
    'background-color' : 'transparent',
    'border' : ''
  });
}

function UpdateWindow(id,name){
  var parentWidth = $("#app"+id).css('width');
  var parentHeight = $("#app"+id).css('height');
  if(parentHeight!='0px'){
    $("#"+name+id).css('width', parentWidth);
    $("#"+name+id).css('height', parentHeight);
  }
}

  $( function() {
    $(window).load(function(){

      <?
      $folder = $_SERVER['DOCUMENT_ROOT'].'/system/core/';
      session_start();
      ?>
      var notificationColor = $(".action-buttons").css('background-color');
      //notification loader

      $.get("<?echo $folder.'functions/NotificationLoader'?>", function(data){
        if(data && (data = $.trim(data))){
          $(data).appendTo("#notification-container");
          var ntf = $(".notificationclass").length;
          if(ntf > 0){
            $("#notification-container").css('display','none');
            $("#notificationsbtn").css({'border':'2px solid '+notificationColor,'background-color':notificationColor});
          }else{
          $("#notificationsbtn").css({'border':'2px solid #fff','background-color':'rgba(0,0,0,0)'});
          }
        }
      });

      //notification checker
      var notificationTimer = setInterval(function(){
        $.get("<?echo $folder.'services/NotificationChecker'?>", function(data){
          if(data && (data = $.trim(data))){
            $("#notification-container").css('display','block');
            $(data).prependTo("#notification-container");
            SaveNotification();
            $("#notificationsbtn").css({'border':'2px solid '+notificationColor,'background-color':notificationColor});
          }
        });
      },10000);

      $(".welcomescreen").hide('fade',500);
      $("#topbar").show('fade', 1500);
      $("#topbar").css('display','block')
      $(".trashdrop").droppable({
        accept: ".ico",
        drop: function(event, ui){
          var del_file = ui.draggable.attr('d');
          $.ajax({
            type: "POST",
            url: "system/core/functions/trash",
            data: {
               file_delete: del_file
            }
          }).done(function(o) {
            $(".link"+ui.draggable.attr('i')).remove();
        });
        }
      });
    });
    $( ".ico" ).draggable({containment:"body", snap:".ico, #topbar"});
    $( ".window" ).mouseup(function(){
      $(".window").removeClass("windowactive")
    });
  });
