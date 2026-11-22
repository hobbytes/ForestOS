<?
/*FOREST GUI*/
date_default_timezone_set('Europe/Moscow');
class gui{
  //для формы
  function formstart($method)
  {
    echo '<div class="guiuser">
          <form method="'.$method.'" action="" enctype="multipart/form-data">';
  }

  function formend()
  {
    echo '</form></div>';
  }
  //для полей
  function inputs($article, $type, $name, $value, $size, $placeholder,$color,$background)
  {
    echo '<label for="'.$name.'" style="color:'.$color.'; background-color:'.$background.'; font-size:15px; border:none; width:'.$size.'%; float:left;">'.$article.':  <input id="'.$name.'" placeholder="'.$placeholder.'"style="width:'.$size.'%; padding:10px; font-size:15px; background-color:#f2f2f2; float:right; border-color:#3a3a3a; border-width:2px; color:#3a3a3a;" type="'.$type.'" name="'.$name.'" value="'.$value.'" /></label><br/>';
  }
  function inputslabel($article, $type, $name, $value, $size, $placeholder)
  {
    echo '<input class="'.$name.'" id="'.$name.'" placeholder="'.$placeholder.'"style="width:'.$size.'%; -webkit-appearance:none; padding:10px; font-size:15px; border-radius:6px; margin:auto; background-color:#fff; float:none; border:1px solid #ccc; color:#3a3a3a;" type="'.$type.'" name="'.$name.'" value="'.$value.'" /></br></br>';
  }

  function errorLayot($content){
    echo "<div onclick='this.remove()' class='ui-forest' style='cursor:pointer; width:300px; margin:auto; position:relative; top:25%; background-color:#f7b1ab; padding:15px; text-align:center; border:2px solid #882720; color:#691812;'><b>#error:</b> $content</div>";
  }
  function warningLayot($content){
    echo "<div onclick='this.remove()' class='ui-forest' style='cursor:pointer; width:300px; margin:auto; position:relative; top:25%; background-color:#f7eeb8; padding:15px; text-align:center; border:2px solid #8e8550; color:#6d6326;'><b>#warning:</b> $content</div>";
  }
  function infoLayot($content){
    echo "<div onclick='this.remove()' class='ui-forest' style='cursor:pointer; width:300px; margin:auto; position:relative; top:25%; background-color:#83c4f1; padding:15px; text-align:center; border:2px solid #29597b; color:#133d5a;'><b>#informaiton:</b> $content</div>";
  }
function getDayRus(){
  $day_lang = parse_ini_file('system/core/os.lang');//localization file
  $days = array(
  $day_lang[$_SESSION['locale'].'_sunday'],
  $day_lang[$_SESSION['locale'].'_monday'],
  $day_lang[$_SESSION['locale'].'_tuesday'],
  $day_lang[$_SESSION['locale'].'_wednesday'],
  $day_lang[$_SESSION['locale'].'_thursday'],
  $day_lang[$_SESSION['locale'].'_friday'],
  $day_lang[$_SESSION['locale'].'_saturday']
  );
 $num_day = (date('w'));
 $name_day = $days[$num_day];
 return $name_day;
}

function dialog($textdialog,$titledialog,$effectdialog){
  $hashid=md5(date('y.m.d.h.i.s'));
  switch($effectdialog){case "bounce": $icon="alert";break; case "highlight": $icon="notice";break; case "fade": $icon="info";break;case "": $icon="blank";break;}
  echo '<div id="'.$hashid.'" title="'.$titledialog.'">
    <p><span class="ui-icon ui-icon-'.$icon.'" style="float:left;margin:0 12px 0 0;"></span>'.$textdialog.'</p>
  </div>';
?>
<script>
$(function(){
  $("#<?echo $hashid;?>").dialog({
    resizable:false,
    height:"auto",
    width:300,
    maxwidth:350,
    modal:true,
    autoOpen:true,
    show:{
      effect:"<?echo $effectdialog;?>",
      duration:500
    },
    buttons:{
      "ок":function(){
        $(this).dialog("close");
        $(this).remove();
      }
    }
  });
});

$(".ui-dialog-titlebar-close").remove();
$(".ui-dialog").css('z-index','9999');
</script>

<?
}

function newnotification($name, $title, $text, $time = 0, $customDate = 0){
  $target = md5($name);
  $uid = $name . md5(date('dmyhis') . mt_rand(0, 9999));
  $date = ($customDate == 0) ? date('H:i, d.m') : $customDate;
  $appName = str_replace('_', ' ', $name);
  ?>
  <div id="notification_<?echo $uid?>" class="<?echo $target?> notificationclass fo-notif" data-notif-app="<?echo htmlspecialchars($appName)?>">
    <div class="fo-notif-top">
      <span class="fo-notif-app"><?echo htmlspecialchars($appName)?></span>
      <span class="fo-notif-time"><?echo htmlspecialchars($date)?></span>
      <button type="button" id="closenot_<?echo $uid?>" class="fo-notif-close" title="Удалить">&#215;</button>
    </div>
    <? if($title !== ''){ ?><div class="fo-notif-title"><?echo $title?></div><? } ?>
    <div class="fo-notif-body"><?echo $text?></div>
  </div>
  <script>
  (function(){
    var el = document.getElementById('notification_<?echo $uid?>');
    if(!el){ return; }
    var close = document.getElementById('closenot_<?echo $uid?>');
    if(close){
      close.onclick = function(){
        if(el.parentNode){ el.parentNode.removeChild(el); }
        if(window.FO_NOTIFY_SYNC){ FO_NOTIFY_SYNC(); }
        if(window.SaveNotification){ SaveNotification(); }
      };
    }
    var box = document.getElementById('notification-container');
    if(box){ box.insertBefore(el, box.firstChild); }
    if(window.FO_NOTIFY_SYNC){ FO_NOTIFY_SYNC(); }
    if(window.SaveNotification){ SaveNotification(); }
  })();
  </script>
  <?
}

}
  ?>
