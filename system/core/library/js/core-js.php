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
  var prc = $(".process").length;
  if (prc > 1){
    $("#fastbuttons").show("fast");
  }else{
    $("#fastbuttons").hide("fast");
  }

}

function makeprocess(dest,  param,  key,  name, data=null){
  $( '.ui-body' ).append("<div id=\"process"+(id=id+1)+"\" class='process' style='display:none;'></div>");
  $( "#process"+id ).load("makeprocess.php?id=<?echo md5(date('d.m.y.h.i.s'));?>"+id+"&d="+dest+"/&i="+id+"&p="+param+"&k="+key+"&n="+name+"&data="+data, null,
    function (responseText, textStatus, XMLHttpRequest, req){
      if(textStatus == "error"){
        $("#" + id).html('<div style="height: 100%; text-align: center; padding:10px; background:#f36d64; color:#731a13; font-size:20px; font-weight:900;">Application start error <br> Status: <b>' + XMLHttpRequest.status +'</b> <br> Status text: <b>' + XMLHttpRequest.statusText + '</b></div>');
      }
    }
  );
  $("#process"+id+"").show('fade', 250);
  checkwindows();
};

function ProcessLogic(id, name, destination, destination_, maxwidthm, folder, isMobile, key, param, autohide, data=null){

  if(data){
    data = JSON.parse(data);
  }

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

  //mouse enter event
  $("#app" + id).mouseenter(function(){
    if(typeof window["mouseEnterApp" + id] == 'function'){
      window["mouseEnterApp" + id]();
    }
  });

  //mouse leave event
  $("#app" + id).mouseleave(function(){
    if(typeof window["mouseLeaveApp" + id] == 'function'){
      window["mouseLeaveApp" + id]();
    }
  });

  //mouse over event
  $("#app" + id).mouseover(function(){
    if(typeof window["mouseOverApp" + id] == 'function'){
      window["mouseOverApp" + id]();
    }
  });

  $("#app" + id).resizable({
    containment:"body",
    minHeight:$(window).height()*0.14,
    minWidth:$(window).width()*0.15,
    maxWidth:$(window).width(),
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
    $("#" + id).load(""+destination+"?id=<?echo rand(0,10000)?>&appid="+id+"&appname="+name+"&destination="+folder+"/&mobile="+isMobile+"&"+key+"="+param, data,
      function (responseText, textStatus, XMLHttpRequest, req){
        if(textStatus == "error"){
          $("#" + id).html('<div style="height: 100%; text-align: center; padding:10px; background:#f36d64; color:#731a13; font-size:20px; font-weight:900;">Application start error <br> Status: <b>' + XMLHttpRequest.status +'</b> <br> Status text: <b>' + XMLHttpRequest.statusText + '</b></div>');
        }
      });
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
    checkwindows();

  });

  $( ".hidewindow" + id ).on( "click", function() {

      runEffect();

      if($("#app" + id).hasClass("ui-resizable")){
          $("#app" + id).resizable({disabled:true,containment:"body"});
        }

        if($("#app" + id).hasClass("windowborderhide")){

          if(typeof window["showApp" + id] == 'function'){
            window["showApp" + id]();
          }

          $("#app" + id).resizable({disabled:false,containment:"body",minHeight:$(window).height()*0.15,minWidth:$(window).width()*0.15,maxWidth:$(window).width(),maxHeight:$(window).height()*0.95,autoHide:autohide,alsoResize:"#"+name+id});
        }else{

          if(typeof window["hideApp" + id] == 'function'){
            window["hideApp" + id]();
          }

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
    }else{
      $("#" + id).load(""+destination_+"?id=<?echo rand(0,10000)?>&appid="+id+"&appname="+name+"&destination="+folder+"/&mobile="+isMobile+"&"+key+"="+param, data,
        function (responseText, textStatus, XMLHttpRequest, req){
          if(textStatus == "error"){
            $("#" + id).html('<div style="height: 100%; text-align: center; padding:10px; background:#f36d64; color:#731a13; font-size:20px; font-weight:900;">Application start error <br> Status: <b>' + XMLHttpRequest.status +'</b> <br> Status text: <b>' + XMLHttpRequest.statusText + '</b></div>');
          }
        });
    }

    let oldbytes = $("#app" + id).attr("applength-" + id);
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

    UpdateWindow(id, name);
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
        $("#app" + id ).css({top:"31px",left:"0px"});

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


    $("#process" + id ).appendTo("#proceses");

    $("#app" + id).focus();

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

  $( ".not-btn" ).on( "click", function() {
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
      var notificationColor = $(".action-buttons").css('background');
      //notification loader

      $.get("<?echo $folder.'functions/NotificationLoader'?>", function(data){
        if(data && (data = $.trim(data))){
          $(data).appendTo("#notification-container");
          var ntf = $(".notificationclass").length;
          if(ntf > 0){
            $("#notification-container").css('display','none');
            $("#notificationsbtn").css({'border':'2px solid transparent', 'background':notificationColor});
          }else{
          $("#notificationsbtn").css({'border':'2px solid #fff','background-color':'rgba(0,0,0,0)'});
          }
        }
      });

      var notificationTimer;
      var CheckStatus = true;

      function CheckNotification(){
        $.get("<?echo $folder.'services/NotificationChecker'?>", function(data){
          if(data && (data = $.trim(data))){
            $("#notification-container").css('display','block');
            $(data).prependTo("#notification-container");
            SaveNotification();
            $("#notificationsbtn").css({'border':'2px solid '+notificationColor,'background-color':notificationColor});
          }
        });
      }

      function StartCheckNotification(){

        if(CheckStatus){
          CheckNotification();
          CheckStatus = false;
        }

        notificationTimer = setInterval(function(){
          CheckNotification();
        },10000);

      }

      function StopCheckNotification(){
        window.clearInterval(notificationTimer);
        CheckStatus = true;
      }

      //start check if window is active
      window.addEventListener('focus', StartCheckNotification);

      //stop check if window is not active
      window.addEventListener('blur', StopCheckNotification);

      $(".welcomescreen").hide('fade', 500);
      $("#topbar").show('fade', 1500);
      $("#topbar").css('display', 'block');

      window.addEventListener("load", function(){
        setTimeout(function(){
          window.scrollTo(0,1);
        }, 0);
      });
    });
    $( ".window" ).mouseup(function(){
      $(".window").removeClass("windowactive")
    });
  });


  var index_ = 1;

  function SetTable(){
    var height = $(window).height();
    var linkTop;
    var newDesktop;

    var cof = 0;
    if($('#topbar').css('display') != 'block'){
      cof = 30;
    }

    if(!index_ || index_ == 0){
      index_ = 1;
    }

    if(height - ($(".desktop").last().find(".link").last().find('.ico').offset().top + cof) < 300){
      index_ = $(".desktop").length + 1;
    }

    /* Calculate */
    $(".desktop").each(function(){
      $(this).find('.link').each(function(){
        linkTop = $(this).find('.ico').offset().top + cof;
        if((height - linkTop) < 300){
          newDesktop = 'desktop-'+index_;
          if(!$.trim($("#"+newDesktop).html()).length){
            $('#desktops').append('<div id="'+newDesktop+'" class="desktop" desktopid="'+index_+'" style="display:block; position: absolute; left: -9999; width: 100vw;"></div>');
            $('.selectors-container').append('<div id="selector-'+index_+'" desktop="'+index_+'" class="ui-forest-blink selector selector-hidden"></div>');
            //$(".link[type=trash]").clone(true, true).appendTo($('#' + newDesktop));
          }
          //if($(this).attr('type') != 'trash'){}
            $(this).appendTo($('#'+ newDesktop));
        }else{

          if($(this).parent().attr('desktopid') > 1){
            desktopid_ = $(".desktop").last().prev().attr('desktopid');

            if(!desktopid_ || desktopid_ == 0){
              desktopid_ = 1;
            }

            if(height - ($("#desktop-" + desktopid_).find(".link").last().find('.ico').position().top + cof) > 410){
                d_ = $(".desktop").last().prev().attr('desktopid');
                $(this).appendTo($('#'+'desktop-' + d_));
            }
          }

        }
      });

    });

    /* Delete desktop if empty */
    $(".selector").each(function(){
      if($("#desktop-"+$(this).attr("desktop")).is(':empty')){
        $("#desktop-"+$(this).attr("desktop")).remove();
        $(this).remove();
        index_ = $(".desktop").last().prev().attr('desktopid');
        if(!index_ || index_ == 0){
          index_ = 1;
        }

        $('.desktop').css({'display':'block', 'position': 'absolute', 'left': '-9999', 'width': '100vw'});
        $('.selector').addClass('selector-hidden');
        $('#selector-' + index_).removeClass('selector-hidden');
        $('#desktop-' + index_).css({'display':'block', 'position': 'unset', 'left': '0', 'width': '100vw'});
      }
    });

    /* Hide selectors if selectors < 1 */
    if($('.selector').length <= 1){
      $('.selectors-container').css('opacity','0');
    }else{
      $('.selectors-container').css('opacity','1');
    }

  }

  /* Destop selectors */
  function SetSelectors() {
    $( ".selector" ).on( "click", function() {
      getDesktop = $(this).attr('desktop');
      $('.desktop').css({'display':'block', 'position': 'absolute', 'left': '-9999', 'width': '100vw'});
      $('.selector').addClass('selector-hidden');
      $('#selector-'+getDesktop).removeClass('selector-hidden');
      $('#desktop-'+getDesktop).css({'display':'block', 'position': 'unset', 'left': '0', 'width': '100vw'});
    });
  }

  $(document).ready(function(){
    if($(window).width() > 0){
      SetTable();
      SetSelectors();
    }
  });

  var resizeid;
  $( window ).resize( function() {
    if($(window).width() > 0){
      SetTable();
      SetSelectors();
      clearTimeout(resizeid);
      resizeid = setTimeout(doneResizing, 250);
    }
  });

  /* Sort link */
  function doneResizing(){
    $(".desktop").each(function(){
      if($(this).css('left') != '0px' || $(this).last().attr('desktopid') == 1){
        $("#" + $(this).attr('id')).find('.link').sort(function (a,b){
          return $(a).attr('link') - $(b).attr('link');
        }).appendTo("#" + $(this).attr('id'));
      }
    });
  }

  function bytesToSize(bytes, decimals) {
    if(bytes == 0){
      return "0 Bytes";
    }
    var k = 1024,
        dm = decimals <= 0 ? 0 : decimals || 2,
        sizes = ["Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"],
        i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
  }
