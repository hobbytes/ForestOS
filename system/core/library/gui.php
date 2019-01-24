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
  //для кнопочек
  function button($article, $textcolor, $backgroundcolor, $size,$name)
  {
    echo '<input class="buttoncustom" style="-webkit-appearance:none; background:'.$backgroundcolor.'; border:none; width:'.$size.'%; color:'.$textcolor.'; padding:10px 20px; border-radius:5px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 0; cursor: pointer;" type="submit" name="'.$name.'" value="'.$article.'" /><br/><br/>';
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
  if($time == 0){
    $time = 10000;
  }
  $_target = md5($name);
  $name = $name.md5(date('dmyhis'));
  if($customDate == 0){
    $date = date('H:i, d.m');
  }else{
    $date = $customDate;
  }
  ?>
  <div id="notification_<? echo $name ?>" class="<? echo $_target ?> topbartheme notificationclass" onmouseover="$('#closenot_<?echo $name?>').css('opacity','1');" onmouseout="$('#closenot_<? echo $name ?>').css('opacity','0');" style="right:0; width:290px; font-size:14px; margin:10px 10px 20px 10px; height:auto; max-height:300px; text-align:left; transition:all 0.2s ease; overflow:auto; ">
  <div id="closenot_<? echo $name ?>" onclick="$('#notification_<?echo $name?>').remove(); SaveNotification();" class="topbartheme" style="position:relative; opacity:0; left:95%; width:8px; background-color:transparent; cursor:pointer; transition:all 0.2s ease; margin:5px 0px 0px -10px;">x</div>
  <span style="font-size:15px; padding: 10px;"><?echo '<b>'.$title.'</b> <span style="font-size:14px;">'.$date.'</span>'?></span><br><br>
  <div style="background-color: #d6d6d6; color: #000; padding: 10px; word-wrap: break-word;"><? echo $text ?></div>

    <div id="script_<? echo $name ?>">
    <script>
    $("#notification_<? echo $name ?>").prependTo("#notification-container");
    $(".notificationclass").css({'opacity':'0','display':'none'});
    $("#notification-container").css('display','block');
    setTimeout(function() {$("#notification_<? echo $name ?>").css('opacity','0.97'); $("#notification_<? echo $name ?>").css('display','block'),1000});
    <? if($time!='infinite'){ ?>
      setTimeout(function() {$("#notification_<? echo $name ?>").css('opacity','0'); $("#notification_<? echo $name ?>").css('display','none');},<? echo $time ?>);
      <? } ?>
      SaveNotification();
      $("#script_<? echo $name ?>").remove();
    </script>
    </div>
    </div>
  <?
}

}
  ?>
