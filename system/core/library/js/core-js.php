function  hibernation(logout){
  $('.process').addClass('hibernatethis');
  var savestate = ($('#proceses').html());
  $.ajax({
    type: "POST",
    url: "system/core/library/etc/hibernation",
    data: {
       content: savestate,
       appid: id
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
  $("#fastbuttons").toggleClass('fo-hidden', prc <= 1);
  if(window.FO_DOCK_SYNC){ FO_DOCK_SYNC(); }

}

function makeprocess(dest,  param,  key,  name, data=null){
  /* the process name becomes part of element ids ("#name<id>"), so it must be
     an identifier: app labels may contain spaces ("Rich Text Editor") */
  var safeName = String(name == null ? '' : name).replace(/[^A-Za-z0-9_\-]/g, '_');
  $( '.ui-body' ).append("<div id=\"process"+(id=id+1)+"\" class='process' style='display:none;'></div>");
  $( "#process"+id ).load("makeprocess.php?id=<?echo md5(date('d.m.y.h.i.s'));?>"+id
      +"&d="+encodeURIComponent(dest)
      +"&i="+id
      +"&p="+encodeURIComponent(param == null ? '' : param)
      +"&k="+encodeURIComponent(key == null ? '' : key)
      +"&n="+encodeURIComponent(safeName)
      +"&data="+encodeURIComponent(data == null ? '' : data), null,
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
    containment: [0, foWindowTopLimit(), window.innerWidth - 2, window.innerHeight - 2],
    handle: "#drag" + id,
    cancel: ".appwindowbutton, .appwindowbutton *",
    snap: snapState,
    start: function(){
      $("#app" + id).data("fo-dragged", false);
    },
    stop: function(){
      if(!$("#app" + id).hasClass("windowfullscreen")){
       $("#app" + id).attr("wt", $("#app" + id).css("top"));
       $("#app" + id).attr("wl", $("#app" + id).css("left"));
     }
     foClampWindow(id);
     // the click that follows a drag must not restore the plaque
     setTimeout(function(){ $("#app" + id).data("fo-dragged", false); }, 60);
   },
   drag: function(){
     $("#app" + id).data("fo-dragged", true);
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
    if(window.FO_SET_BAR_TITLE){ window.FO_SET_BAR_TITLE(name); }

    if(typeof window["activeApp" + id] == 'function'){
      window["activeApp" + id]();
    }

  });

  $( "#drag" + id ).click(function(){
    $(".window").removeClass("windowactive")
    $("#app" + id ).addClass("windowactive");
    if(window.FO_SET_BAR_TITLE){ window.FO_SET_BAR_TITLE(name); }
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
    if(window.FO_SET_BAR_TITLE){ window.FO_SET_BAR_TITLE(name); }
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
    (function(){
      var $rest = $(".window").last();
      if($rest.length){
        $rest.addClass("windowactive");
        if(window.FO_SET_BAR_TITLE){ window.FO_SET_BAR_TITLE($rest.find(".process-title-text").text()); }
      }else if(window.FO_SET_BAR_TITLE){
        window.FO_SET_BAR_TITLE("Forest OS");
      }
    })();

  });

  $( ".hidewindow" + id ).on( "click", function() {
    if(typeof window["hideApp" + id] == 'function'){ window["hideApp" + id](); }
    foMinimizeWindow(id);

    });

  $(".reload" + id).on( "click", function() {
    var started = Date.now();
    foBusyWindow(id, true);

    function finish(){
      var wait = Math.max(0, 320 - (Date.now() - started));
      setTimeout(function(){
        foBusyWindow(id, false);
        if(window.FO_TOAST){ FO_TOAST("Приложение перезагружено"); }
      }, wait);
    }

    if(typeof window["reloadApp" + id] == 'function'){
      window["reloadApp" + id]();
      finish();
    }else{
      $("#" + id).load(""+destination_+"?id=<?echo rand(0,10000)?>&appid="+id+"&appname="+name+"&destination="+folder+"/&mobile="+isMobile+"&"+key+"="+param, data,
        function (responseText, textStatus, XMLHttpRequest, req){
          if(textStatus == "error"){
            $("#" + id).html('<div style="height: 100%; text-align: center; padding:10px; background:#f36d64; color:#731a13; font-size:20px; font-weight:900;">Application start error <br> Status: <b>' + XMLHttpRequest.status +'</b> <br> Status text: <b>' + XMLHttpRequest.statusText + '</b></div>');
            if(window.FO_TOAST){ FO_TOAST("Не удалось перезагрузить приложение"); }
          }
          finish();
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

  $( "#drag" + id ).on( "dblclick", function() {
    // a rolled-up plaque is restored by double click, not maximised
    if($( "#app" + id ).hasClass("windowborderhide")){
      foUnshadeWindow(id);
      return;
    }
    if(!$( "#app" + id ).hasClass("windowborderhide")){
      if(!$( "#app" + id ).hasClass("windowfullscreen")){
        $( "#app" + id ).attr("ww", $("#"+name+id).css("width"));
        $( "#app" + id ).attr("wh", $("#"+name+id).css("height"));
        $( "#app" + id ).attr("wt", $("#app" + id).css("top"));
        $( "#app" + id ).attr("wl", $("#app" + id).css("left"));
        $( "#app" + id ).css({
          "top" : "31px",
          "left" : "0px",
          "width" : "",
          "height" : "",
          "box-shadow": "unset"
        });
        $( "#drag" + id ).css('border-radius', '0');

        if(typeof window["windowFullScreenApp" + id] == 'function'){
          window["windowFullScreenApp" + id]();
        }
      }else{
        $("#app" + id ).css('box-shadow', 'rgba(0, 0, 0, 0.5) 0px 0px 25px -4px');
        $( "#drag" + id ).css('border-radius', '3px 3px 0 0');
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


    // Title bar context menu (reload / maximise / close) — the compact
    // Hyprland-style bar only has three controls, reload lives here.
    $( "#drag" + id ).on( "contextmenu", function(e){
      e.preventDefault();
      $( "#fo-window-menu" ).remove();
      var $menu = $( '<div id="fo-window-menu"></div>' ).css({
        position: 'absolute', left: e.pageX, top: e.pageY, zIndex: 100002, padding: '5px',
        minWidth: '196px', borderRadius: '9px', border: '1px solid rgba(255,255,255,0.14)',
        background: 'rgba(24,24,26,0.96)', color: '#f2f2f4', boxShadow: '0 14px 38px rgba(0,0,0,0.5)',
        backdropFilter: 'blur(16px)',
        font: '500 12.5px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif'
      }).appendTo( 'body' );

      function addItem(label, fn){
        $( '<div></div>' ).text( label ).css({ padding: '7px 11px', borderRadius: '6px', cursor: 'default' })
          .hover(function(){ $( this ).css( 'background', 'rgba(255,255,255,0.12)' ); },
                 function(){ $( this ).css( 'background', 'transparent' ); })
          .on( 'click', function(){ $menu.remove(); fn(); } )
          .appendTo( $menu );
      }

      addItem( 'Развернуть / Восстановить', function(){ $( "#drag" + id ).trigger( 'dblclick' ); } );
      addItem( 'Перезагрузить приложение', function(){ $( ".reload" + id ).trigger( 'click' ); } );
      addItem( 'Закрыть', function(){ $( ".close" + id ).trigger( 'click' ); } );

      setTimeout(function(){
        $( document ).one( 'mousedown', function(ev){
          if($(ev.target).closest('#fo-window-menu').length){ return; }
          $menu.remove();
        } );
      }, 0);
    });

    $("#process" + id ).appendTo("#proceses");
    foRaiseWindow(id);

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
    $("#notifications").toggleClass("notificationshow");
    if(window.FO_NOTIFY_SYNC){ FO_NOTIFY_SYNC(); }
    return false;
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
  $( ".ico" ).css('z-index', '-100');
}

function UpdateWindow(id, name, mode = 1){
  var $win = $("#app" + id);
  var $box = $("#" + name + id);
  if(!$win.length || !$box.length){ return; }
  if($box.is(':hidden')){ return; }

  // The window height includes the title bar, the app container must not:
  // otherwise every update grows the window by the bar height.
  var barH = $win.find('.dragwindow').first().outerHeight() || 0;
  var w = $win.width();
  var h = $win.height() - barH;

  $box.css('width', w);
  if(h > 40){
    $box.css('height', h);
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
          if(window.FO_NOTIFY_SYNC){ FO_NOTIFY_SYNC(); }
        }
      });

      var notificationTimer;
      var CheckStatus = true;

      function CheckNotification(){
        $.get("<?echo $folder.'services/NotificationChecker'?>", function(data){
          if(data && (data = $.trim(data))){
            $(data).prependTo("#notification-container");
            SaveNotification();
            if(window.FO_NOTIFY_SYNC){ FO_NOTIFY_SYNC(); }
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
    if(window.FO_DESKTOP_HAS_LAYOUT){ return; }
    // nothing to arrange: an empty desktop must not break the shell JS
    if(!$(".desktop .link .ico").length){ return; }
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

  //Clear Notification Function
  function NotificationClear(){
    $("#notification-container").html('');
    SaveNotification();
    $("#notificationsbtn").removeClass('has-notifications');
  }

  // bytes to size
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

  //Save Notification Function
  function SaveNotification(){
    var content = $("#notification-container").html();
    $.ajax({
      type: "POST",
      url: "<?echo $folder.'functions/NotificationSaver'?>",
      data: {
         login:"<?echo $_SESSION['loginuser']?>",
         body:content
      },
      success: function(datas){
        //console.log(datas);
      }
    });
  }

/* ==========================================================================
   Forest OS — окна в доке и центр уведомлений
   ========================================================================== */

/* ==========================================================================
   Forest OS — окна: z-order, плашки, док
   ========================================================================== */

var FO_ZTOP = 100;

function foWindowIdOf(el){
  return String((el && el.id) || "").replace("app", "");
}

/* All windows share one z-counter, otherwise a focused window with an
   inline z-index paints over a freshly opened one (which has none). */
function foRaiseWindow(id){
  var $w = $("#app" + id);
  if(!$w.length){ return; }
  FO_ZTOP++;
  $w.css("z-index", FO_ZTOP);
  $(".window").removeClass("windowactive");
  $w.addClass("windowactive");
  if(window.FO_SET_BAR_TITLE){
    FO_SET_BAR_TITLE($w.find(".process-title-text").text());
  }
}

function foFlashWindow(id){
  var $w = $("#app" + id);
  if(!$w.length){ return; }
  $w.addClass("fo-flash");
  setTimeout(function(){ $w.removeClass("fo-flash"); }, 620);
}

function foFocusWindow(id){
  var $w = $("#app" + id);
  if(!$w.length){ return; }
  foRaiseWindow(id);
  foFlashWindow(id);
}

/* ---------- occupied / busy ---------- */
function foBusyWindow(id, on){
  var $w = $("#app" + id);
  if(!$w.length){ return; }
  $w.toggleClass("fo-busy", !!on);
}

/* ---------- rolled-up plaques ---------- */
function foIsShaded($w){
  return !!$w && $w.hasClass("windowborderhide");
}

function foShadeWindow(id){
  var $w = $("#app" + id);
  if(!$w.length){ return; }
  if(foIsShaded($w)){ return foUnshadeWindow(id); }

  $w.data("fo-pre-shade", {
    width: $w[0].style.width,
    height: $w[0].style.height,
    left: $w[0].style.left,
    top: $w[0].style.top
  });
  $w.addClass("windowborderhide");
  $w.find(".dragwindow").addClass("dragwindowtoggle");
  // let the stylesheet shrink it to a pill
  $w.css({ width: "", height: "", "min-width": "", "max-width": "" });
  FO_DOCK_SYNC();
}

function foUnshadeWindow(id){
  var $w = $("#app" + id);
  if(!$w.length){ return; }
  if(!foIsShaded($w) && $w.css("display") !== "none"){ return foFocusWindow(id); }

  var pre = $w.data("fo-pre-shade") || {};
  $w.removeClass("windowborderhide").removeAttr("data-minimized").show();
  $w.find(".dragwindow").removeClass("dragwindowtoggle");
  if(pre.width){ $w.css("width", pre.width); }
  if(pre.height){ $w.css("height", pre.height); }
  if(pre.left){ $w.css("left", pre.left); }
  if(pre.top){ $w.css("top", pre.top); }
  foFocusWindow(id);
  if(window.FO_CLAMP_WINDOW){ window.FO_CLAMP_WINDOW(id); }
  FO_DOCK_SYNC();
}

/* kept as the public name used by the title-bar button */
function foMinimizeWindow(id){
  foShadeWindow(id);
}

function foRestoreWindow(id){
  foUnshadeWindow(id);
  return false;
}

function foWindowsFor(app){
  return $('.window[data-app="' + app + '"]');
}

function foWindowId($w){
  return String($w.attr("id") || "").replace("app", "");
}

function foIsCollapsed($w){
  return $w.css("display") === "none" || $w.attr("data-minimized") === "1" || foIsShaded($w);
}

function foHiddenWindowFor(app){
  return foWindowsFor(app).filter(function(){
    return foIsCollapsed($(this));
  }).last();
}

function foDockClick(app){
  var $all = foWindowsFor(app);
  if(!$all.length){
    if(typeof window.makeprocess === "function"){
      window.makeprocess("system/apps/" + app + "/main.php", "", "", app);
    }
    return false;
  }

  // 1) есть свёрнутое/плашка — вернуть
  var $min = foHiddenWindowFor(app);
  if($min.length){
    foRestoreWindow(foWindowId($min));
    return false;
  }

  // 2) окна уже на экране — переключить фокус (повторный клик листает окна)
  var $vis = $all.filter(function(){ return $(this).css("display") !== "none"; });
  if($vis.length){
    var $active = $vis.filter(".windowactive");
    var $next;
    if($active.length && $vis.length > 1){
      var idx = $vis.index($active.last());
      $next = $vis.eq((idx + 1) % $vis.length);
    }else{
      $next = $active.length ? $active.last() : $vis.last();
    }
    if($next && $next.length){
      foFocusWindow(foWindowId($next));
      return false;
    }
  }

  if(typeof window.makeprocess === "function"){
    window.makeprocess("system/apps/" + app + "/main.php", "", "", app);
  }
  return false;
}

function FO_DOCK_SYNC(){
  $(".dock-icon[data-app]").each(function(){
    var app = $(this).attr("data-app");
    var $w = foWindowsFor(app);
    var total = $w.length;
    var collapsed = $w.filter(function(){ return foIsCollapsed($(this)); }).length;
    $(this).toggleClass("is-running", total > 0);
    $(this).toggleClass("is-minimized", total > 0 && collapsed === total);
    var title = app;
    if(total > 0 && collapsed === total){ title = app + " · свёрнуто"; }
    else if(total > 1){ title = app + " · окон: " + total; }
    $(this).attr("title", title);
  });
}

function foWindowTopLimit(){
  return ($(window).width() <= 760) ? 44 : 34;
}

/* windows never hide above the bar or outside the screen */
function foClampWindow(id){
  var $w = $("#app" + id);
  if(!$w.length || $w.hasClass("windowfullscreen")){ return; }
  var off = $w.offset();
  if(!off){ return; }
  var vw = $(window).width();
  var vh = $(window).height();
  var w = $w.outerWidth() || 400;
  var minTop = foWindowTopLimit();
  var x = off.left;
  var y = off.top;
  if(y < minTop){ y = minTop; }
  if(y > vh - 40){ y = Math.max(minTop, vh - 40); }
  if(x + w < 90){ x = 90 - w; }
  if(x > vw - 90){ x = vw - 90; }
  if(Math.round(x) !== Math.round(off.left) || Math.round(y) !== Math.round(off.top)){
    $w.css({ left: Math.round(x), top: Math.round(y) });
  }
}

window.FO_CLAMP_WINDOW = foClampWindow;

$(window).on("resize", function(){
  $(".window").each(function(){
    foClampWindow(String(this.id || "").replace("app", ""));
  });
});

/* text context menu: copy/paste without the browser's own menu */
function foTextCopy(text){
  if(navigator.clipboard && navigator.clipboard.writeText){
    return navigator.clipboard.writeText(text);
  }
  var ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  try { document.execCommand('copy'); } catch(e){}
  document.body.removeChild(ta);
  return Promise.resolve();
}

function foTextMenu(x, y){
  $('#fo-text-menu').remove();
  var sel = '';
  try { sel = String(window.getSelection ? window.getSelection().toString() : ''); } catch(e){}
  var el = document.activeElement;
  var tag = el && el.tagName ? el.tagName.toLowerCase() : '';
  var isField = (tag === 'input' || tag === 'textarea');

  var $m = $('<div id="fo-text-menu"></div>').css({ left: x, top: y }).appendTo('body');

  function item(label, hint, fn, enabled){
    var $i = $('<div class="fo-tm-item' + (enabled ? '' : ' is-off') + '"></div>')
      .append($('<span></span>').text(label))
      .append($('<span></span>').text(hint || '').css('opacity', '.5'));
    if(enabled){
      $i.on('click', function(){ $m.remove(); fn(); });
    }
    return $i.appendTo($m);
  }

  item('Копировать', '\u2318C', function(){
    foTextCopy(sel);
  }, sel.length > 0);

  item('Вырезать', '\u2318X', function(){
    if(!isField){ return; }
    var start = el.selectionStart, end = el.selectionEnd;
    var piece = String(el.value || '').substring(start, end);
    foTextCopy(piece);
    el.setRangeText('', start, end, 'end');
    $(el).trigger('input');
  }, isField && el.selectionStart !== el.selectionEnd);

  item('Вставить', '\u2318V', function(){
    if(!isField){ return; }
    if(navigator.clipboard && navigator.clipboard.readText){
      navigator.clipboard.readText().then(function(text){
        var start = el.selectionStart, end = el.selectionEnd;
        el.setRangeText(text, start, end, 'end');
        $(el).trigger('input');
      }).catch(function(){});
    }
  }, isField);

  $('<div class="fo-tm-sep"></div>').appendTo($m);

  item('Выделить всё', '\u2318A', function(){
    if(isField && el.select){ el.select(); return; }
    var r = document.createRange();
    r.selectNodeContents(document.body);
    var s = window.getSelection();
    s.removeAllRanges();
    s.addRange(r);
  }, true);

  var mw = $m.outerWidth() || 190;
  var mh = $m.outerHeight() || 120;
  var left = Math.max(8, Math.min(x, $(window).width() - mw - 8));
  var top = Math.max(8, Math.min(y, $(window).height() - mh - 8));
  $m.css({ left: left, top: top });

  setTimeout(function(){
    $(document).one('mousedown', function(e){
      if(!$(e.target).closest('#fo-text-menu').length){ $('#fo-text-menu').remove(); }
    });
    $(document).one('keydown', function(){ $('#fo-text-menu').remove(); });
  }, 0);
}

/* text context menu (copy/cut/paste). Other areas are handled by the shell:
   windows, dock, desktop shortcuts and the desktop itself have their own menus. */
$(document).on("contextmenu", function(e){
  if(e.isDefaultPrevented && e.isDefaultPrevented()){ return; }
  var t = e.target;
  var tag = (t && t.tagName) ? t.tagName.toLowerCase() : '';
  var sel = '';
  try { sel = String(window.getSelection ? window.getSelection().toString() : ''); } catch(err){}
  if(tag === "input" || tag === "textarea" || sel.length > 0){
    e.preventDefault();
    foTextMenu(e.pageX, e.pageY);
  }
});

/* one window manager for every window */
$(document).on("mousedown", ".window", function(e){
  foRaiseWindow(foWindowIdOf(this));
  // remember where the press started: a plaque must not restore after a drag
  $(this).data("fo-down", { x: e.pageX, y: e.pageY });
});

$(document).on("click", ".window.windowborderhide", function(e){
  // a plaque is restored by a simple click (buttons keep their own behaviour)
  if($(e.target).closest(".appwindowbutton").length){ return; }
  if($(this).data("fo-dragged")){
    $(this).data("fo-dragged", false);
    return;
  }
  foUnshadeWindow(foWindowIdOf(this));
});

window.FO_RAISE_WINDOW = foRaiseWindow;
window.FO_SHADE_WINDOW = foShadeWindow;
window.FO_UNSHADE_WINDOW = foUnshadeWindow;
window.FO_BUSY_WINDOW = foBusyWindow;
window.FO_MINIMIZE = foMinimizeWindow;
window.FO_RESTORE = foRestoreWindow;
window.FO_DOCK_CLICK = foDockClick;
function foDockFolder(rel, title){
  rel = String(rel || '');
  var found = false;
  $('.window[data-app="Explorer"]').each(function(){
    var c = $(this).find('.fo-content');
    var dir = c.length ? String(c.attr('data-dir') || '') : '';
    if(dir && dir.indexOf(rel) !== -1){
      foFocusWindow(foWindowIdOf(this));
      found = true;
      return false;
    }
  });
  if(!found && typeof window.makeprocess === "function"){
    window.makeprocess("system/apps/Explorer/main.php", rel, "defaultloader", "Explorer");
  }
  return false;
}

window.FO_DOCK_FOLDER = foDockFolder;
window.FO_DOCK_SYNC = FO_DOCK_SYNC;

var FO_TOAST_SEEN = {};
var FO_TOAST_BASELINE = null;   /* уведомления, которые уже были при загрузке — не всплывают */

/* маленькие всплывающие уведомления справа сверху, как в старом дизайне */
function foNotifToasts(){
  var $stack = $('#fo-notif-toasts');
  if(!$stack.length){ $stack = $('<div id="fo-notif-toasts"></div>').appendTo('body'); }

  /* ждём, пока страница загрузит сохранённые уведомления: они не всплывают */
  if(FO_TOAST_BASELINE === null){ return; }

  $('#notification-container .notificationclass').each(function(){
    var el = this;
    var id = el.id || '';
    if(!id){ return; }
    if(FO_TOAST_SEEN[id]){ return; }
    FO_TOAST_SEEN[id] = 1;
    if(FO_TOAST_BASELINE[id]){ return; }

    var app = $(el).find('.fo-notif-app').first().text();
    var title = $(el).find('.fo-notif-title').first().text();
    var body = $(el).find('.fo-notif-body').first().text();
    if(!title && !body){ return; }

    var $c = $('<div class="fo-notif-toast"></div>');
    $c.append($('<div class="fo-nt-app"></div>').text(app || 'Forest OS'));
    if(title){ $c.append($('<div class="fo-nt-title"></div>').text(title)); }
    $c.append($('<div class="fo-nt-body"></div>').text(body));
    $c.on('click', function(){
      $('#notifications').addClass('notificationshow');
      $c.remove();
    });
    $stack.append($c);
    setTimeout(function(){
      $c.addClass('fo-out');
      setTimeout(function(){ $c.remove(); }, 300);
    }, 5200);
    while($stack.children().length > 4){ $stack.children().first().remove(); }
  });

}

$(function(){
  setTimeout(function(){
    FO_TOAST_BASELINE = FO_TOAST_BASELINE || {};
    $('#notification-container .notificationclass').each(function(){
      if(this.id){ FO_TOAST_BASELINE[this.id] = 1; FO_TOAST_SEEN[this.id] = 1; }
    });
  }, 900);
});

function FO_NOTIFY_SYNC(){
  var $box = $("#notification-container");
  if(!$box.length){ return; }
  // старый дизайн прятал контейнер и вешал inline display — снимаем
  $box.css({ display: "", opacity: "" });
  $box.find(".notificationclass").css({ display: "", opacity: "" });
  var n = $box.find(".notificationclass").length;
  $("#fo-notif-count").text(n).toggleClass("fo-hidden", n === 0);
  $("#fo-notif-empty").toggleClass("fo-hidden", n > 0);
  $("#notificationsbtn").toggleClass("has-notifications", n > 0);

  foNotifToasts();
}

window.FO_NOTIFY_SYNC = FO_NOTIFY_SYNC;

$(function(){
  FO_NOTIFY_SYNC();
  var box = document.getElementById("notification-container");
  if(box && window.MutationObserver){
    new MutationObserver(function(){ FO_NOTIFY_SYNC(); }).observe(box, { childList: true });
  }
  $(document).on("keydown", function(e){
    if(e.key === "Escape"){ $("#notifications").removeClass("notificationshow"); }
  });
  $(document).on("mousedown", function(e){
    if(!$(e.target).closest("#notifications, #notificationsbtn").length){
      $("#notifications").removeClass("notificationshow");
    }
  });
});
