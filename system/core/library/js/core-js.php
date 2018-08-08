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
  $("#process"+id+"").show('fade', 250);
  checkwindows();
};

function ProcessLogic(id, name, destination, destination_, maxwidthm, folder, isMobile, key, param, autohide){

  let snapState = null;
  if(isMobile == "false"){
    snapState = ".ui-body, .dragwindowtoggle, #topbar";
  }

  $("#app" + id).draggable({
    containment: "body",
    handle: "#drag" + id,
    snap: snapState,
    stop: function(){
      if(!$("#app" + id).hasClass("windowfullscreen")){
       $("#app" + id).attr("wt", $("#app" + id).css("top"));
       $("#app" + id).attr("wl", $("#app" + id).css("left"));
     }
   },
   drag: function(){
     if(typeof window["moveApp" + id] == 'function'){
       window["moveApp" + id]();
     }
   }
  });

  $("#app" + id).resizable({
    containment:"body",
    minHeight:$(window).height()*0.14,
    minWidth:$(window).width()*0.15,
    maxWidth:$(window).width()*maxwidthm,
    maxHeight:$(window).height()*0.96,
    autoHide:autohide,
    alsoResize:"#"+name+id
  });

  $("#app" + id).click(function(){
    $(".window").removeClass("windowactive")
    $("#app" + id ).addClass("windowactive");

    if(typeof window["activeApp" + id] == 'function'){
      window["activeApp" + id]();
    }

  });

  $( "#drag" + id ).click(function(){
    $(".window").removeClass("windowactive")
    $("#app" + id ).addClass("windowactive");
  });

  if(!$("#process" + id).hasClass('hibernatethis')){
    $("#" + id).load(""+destination+"?id=<?echo rand(0,10000)?>&appid="+id+"&appname="+name+"&destination="+folder+"/&mobile="+isMobile+"&"+key+"="+param);
  }

  $(function() {
    $(".window").removeClass("windowactive");
    $("#app" + id ).addClass("windowactive");
  });

  function runEffect() {
    var options = {};
    $( "#" + id).toggle( "slide", options, 100 );
  };

  $(".close" + id).on( "click", function() {

    if(typeof window["closeApp" + id] == 'function'){
      window["closeApp" + id]();
    }

    $("#process" + id).remove();

  });

  $( ".hidewindow" + id ).on( "click", function() {

    if(typeof window["hideApp" + id] == 'function'){
      window["hideApp" + id]();
    }

      runEffect();

      if($("#app" + id).hasClass("ui-resizable")){
          $("#app" + id).resizable({disabled:true,containment:"body"});
        }

        if($("#app" + id).hasClass("windowborderhide")){
          $("#app" + id).resizable({disabled:false,containment:"body",minHeight:$(window).height()*0.15,minWidth:$(window).width()*0.15,maxWidth:$(window).width()*maxwidthm,maxHeight:$(window).height()*0.95,autoHide:autohide,alsoResize:"#"+name+id});
        }else{
          $("#app" + id).css({
            'width':'auto',
            'height' : 'auto'
          });
        }

        $( "#drag" + id ).toggleClass( "dragwindowtoggle", 500 );
        $("#app" + id).toggleClass( "windowborderhide", 500 );
        $("#app" + id).toggleClass( "bordertoggle", 1 );
    });

  $(".reload" + id).on( "click", function() {

    if(typeof window["reloadApp" + id] == 'function'){
      window["reloadApp" + id]();
    }

    let oldbytes = $("#app" + id).attr("applength-" + id);
    $("#" + id).load(""+destination_+"?id=<?echo rand(0,10000)?>&appid="+id+"&appname="+name+"&destination="+folder+"/&mobile="+isMobile+"&"+key+"="+param);
    $("#app" + id).attr("applength-" + id, parseInt(oldbytes) + $("#" + name + id).html().length);
  });

  $("#app" + id).resize(function(){

    if(typeof window["resizeApp" + id] == 'function'){
      window["resizeApp" + id]();
    }

    if(!$("#app" + id).hasClass("windowfullscreen")){
      $("#app" + id).attr("ww", $("#"+name+id).css("width"));
      $("#app" + id).attr("wh", $("#"+name+id).css("height"));
    }
    $("#app" + id ).addClass("windowactive");
  });

  $("#drag" + id ).on( "dblclick", function() {
    if(!$("#app" + id).hasClass("windowborderhide")){
      if(!$("#app" + id).hasClass("windowfullscreen")){
        $("#app" + id).attr("ww", $("#"+name+id).css("width"));
        $("#app" + id).attr("wh", $("#"+name+id).css("height"));
        $("#app" + id).attr("wt", $("#app" + id).css("top"));
        $("#app" + id).attr("wl", $("#app" + id).css("left"));
        $("#app" + id).css("width","");
        $("#app" + id).css("height","");
        $("#app" + id ).css({top:"31px",left:"2px"});

        if(typeof window["windowFullScreenApp" + id] == 'function'){
          window["windowFullScreenApp" + id]();
        }
      }

      if($("#app" + id).hasClass("windowfullscreen")){
        $("#"+name+id).css("width", $("#app" + id).attr("ww"));
        $("#"+name+id).css("height", $("#app" + id).attr("wh"));
        $("#app" + id).css("top", $("#app" + id).attr("wt"));
        $("#app" + id).css("left", $("#app" + id).attr("wl"));

        if(typeof window["windowNormalScreenApp" + id] == 'function'){
          window["windowNormalScreenApp" + id]();
        }

      }
      $("#app" + id ).toggleClass( "windowfullscreen", 100, function(){
          UpdateWindow(id, name, 0);
        });
    }
    });

    $("#app" + id).resize(function(){
      UpdateWindow(id, name);
    });
    $("#process" + id ).appendTo("#proceses");
};

<?
if(!isset($_SESSION)){
  session_start();
}
$timezone = $_SESSION["timezone"];
date_default_timezone_set("$timezone");
$date = date("Y-m-d H:i:s");
$_offset = new DateTime($date, new DateTimeZone("$timezone"));
$offset = $_offset->getOffset()/3600;

?>
function showTime()
{
  offset = "<?echo $offset?>"
  d = new Date();   
  utc = d.getTime() + (d.getTimezoneOffset() * 60000);   
  date = new Date(utc + (3600000*offset)); 
  var H = '' + date.getHours();
  H = H.length < 2 ? '0' + H:H;
  var M = '' + date.getMinutes();
  M = M.length < 2 ? '0' + M:M;
  var S = '' + date.getSeconds();
  S =S.length < 2 ? '0' + S:S;
  var clock = H + ":" + M;
  $("#time").text(clock);
  var clock_ = H + ":" + M + ":" + S;
  $("#fulltime").text(clock_);
  setTimeout(showTime,1000); // перерисовать 1 раз в сек.
}
showTime();

$( function() {

    $( "#desktops" ).on( "click", function() {
      $(".window").removeClass("windowactive")
    })

    $( "#background-wall" ).on( "click", function() {
      $(".window").removeClass("windowactive")
    })

    $( "#topbar" ).on( "click", function() {
      $(".window").removeClass("windowactive")
    })

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

function UpdateWindow(id, name, mode = 1){
  parentWidth = $("#app"+id).css('width');
  parentHeight = $("#app"+id).css('height');

  if(parentHeight != '0px'){

    //get difference
    if(!$("#app" + id).hasClass("windowfullscreen")  && mode == 0){
      match = 0;
      match = $("#app"+id).height() - $("#"+name+id).height();
      parentHeight = $("#app"+id).height() - match + "px";
    }

    //update window
    $("#"+name+id).css('width', parentWidth);
    $("#"+name+id).css('height', parentHeight);
  }
}

  $( function() {
    $(window).load(function(){

      <?
      $folder = $_SERVER['DOCUMENT_ROOT'].'/system/core/';
      if(!isset($_SESSION)){
        session_start();
      }
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


  var index_ = 1;
  var s_index_ = 0;
  function SetTable(){
    var height = $(window).height();
    //console.log(height);
    var linkTop;

    var cof = 0;
    if($('#topbar').css('display') != 'block'){
      cof = 30;
    }

    if(height - ($(".link").last().find('.ico').position().top + cof) < 300){
      index_ = $(".desktop").length + 1;
    }

    $(".link").each(function(){
        linkTop = $(this).find('.ico').position().top + cof;
        //console.log($(this).find('.linktheme').text() + ':' + linkTop);
        if((height - linkTop) < 300){
          var newDesktop = 'desktop-'+index_;
          if(!$('#'+newDesktop).length){
            $('#desktops').append('<div id="'+newDesktop+'" class="desktop" style="display:none;"></div>');
            //$(".link[type='trash']").clone().prependTo($('#'+newDesktop));
            $('.selectors-container').append('<div id="selector-'+index_+'" desktop="'+index_+'" class="ui-forest-blink selector selector-hidden"></div>');
            $(this).prependTo($('#'+newDesktop));
          }else if($(this).attr('type') != 'trash'){
            $(this).prependTo($('#'+newDesktop));
          }
        }
    });

    if($('.selector').length <= 1){
      $('.selectors-container').css('display','none');
    }else{
      $('.selectors-container').css('display','block');
    }

    if (s_index_ < index_){
      s_index_++;
      //SetTable();
      //console.log(s_index_ +':'+ index_);
    }

  }

  /*Destop selectors*/
  function SetSelectors() {
    $( ".selector" ).on( "click", function() {
      getDesktop = $(this).attr('desktop');
      $('.desktop').css('display','none');
      $('.selector').addClass('selector-hidden');
      $('#selector-'+getDesktop).removeClass('selector-hidden');
      $('#desktop-'+getDesktop).css('display','block');
    });
  }

  $(document).ready(function(){
    SetTable();
    SetSelectors();
  });


  $( window ).resize( function() {
    SetTable();
    SetSelectors();
  });
