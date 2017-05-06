<?
/*FOREST GUI*/
date_default_timezone_set('Europe/Moscow');
class gui{
  //для регистарции
  function registration($article, $type, $name, $value)
  {
    echo '<div style="color:#f2f2f2; -webkit-appearance:none; ">'.$article.':</div>  <input style="-webkit-appearance:none; font-size:20px; margin-bottom:10px; background-color:#494949; border-color:#3a3a3a; border:1; color:#f2f2f2;" type="'.$type.'" name="'.$name.'" value="'.$value.'" /><br/>';
  }
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
  //для скрытых полей
  function inputhidden($name,$value)
  {
    echo '<input id="'.$name.'" type="hidden" value="'.$value.'" name="'.$name.'"/>';
  }
  //для кнопочек
  function button($article, $textcolor, $backgroundcolor, $size,$name)
  {
    echo '<input class="buttoncustom" style="-webkit-appearance:none; background-color:'.$backgroundcolor.'; border:none; width:'.$size.'%; color:'.$textcolor.'; padding:10px 20px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; margin: 0; cursor: pointer;" type="submit" name="'.$name.'" value="'.$article.'" /><br/><br/>';
  }
  function inputslabel($article, $type, $name, $value, $size, $placeholder)
  {
    echo '<input class="'.$name.'" id="'.$name.'" placeholder="'.$placeholder.'"style="width:'.$size.'%; -webkit-appearance:none; padding:10px; font-size:15px; margin:auto; background-color:#fff; float:none; border:1px solid #ccc; color:#3a3a3a;" type="'.$type.'" name="'.$name.'" value="'.$value.'" /></br></br>';
  }
function getDayRus(){
 $days = array(
 'Воскресенье' , 'Понедельник' ,
'Вторник' , 'Среда' ,
 'Четверг' , 'Пятница' , 'Суббота'
 );
$num_day = (date('w'));
$name_day = $days[$num_day];
 return $name_day;
}

function dialog($textdialog,$titledialog,$effectdialog){
  $hashid=md5(date('y.m.d.h.i.s'));
  switch($effectdialog){case "bounce": $icon="alert";break; case "highlight": $icon="notice";break; case "fade": $icon="info";break;case "": $icon="blank";break;}
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
      }
    }
  });
});
</script>

<?
echo '<div id="'.$hashid.'" title="'.$titledialog.'">
  <p><span class="ui-icon ui-icon-'.$icon.'" style="float:left;margin:0 12px 0 0;"></span>'.$textdialog.'</p>
</div>';
}

function newnotification($name, $title, $text,$time){
  if(!isset($time)){$time=10000;}
  $name=$name.md5(date('dmyhis'));
  ?>

  <div id="notification_<?echo $name;?>" class="topbartheme notificationclass" onmouseover="document.getElementById('closenot_<?echo $name;?>').style.opacity='1';" onmouseout="document.getElementById('closenot_<?echo $name;?>').style.opacity='0';" style="right:0; width:300px; margin:10px 10px 20px 10px; height:auto; max-height:300px; text-align:left; transition:all 0.2s ease; overflow:auto; border-radius:10px; ">
  <div id="closenot_<?echo $name;?>" onclick="document.getElementById('notification_<?echo $name;?>').remove();" class="topbartheme" style="position:relative; opacity:0; left:95%; width:8px; background-color:transparent; cursor:pointer; transition:all 0.2s ease; margin:10px 0px 0px -10px;">x</div>
<span style="font-size:17px; padding: 10px;"><?echo '<b>'.$title.'</b> <span style="font-size:14px;">'.date('H:i, d.m').'</span>'?></span><br><br>
    <div style="background-color: #d6d6d6;color: #000;padding: 10px;"><?echo $text;?></div>

    <script>
    var notcenter=document.getElementById('notifications');
    var notcontent=document.getElementById('notification_<?echo $name;?>');
    notcenter.appendChild(notcontent);
    setTimeout(function() {$("#notification_<?echo $name;?>").css('opacity','0.97'),1000});
    <?if($time!='infinite'){?>
  setTimeout(function() {$("#notification_<?echo $name;?>").css('opacity','0');},<?echo $time;?>);
<?}?>
    </script>
    </div>
  <?
}

}
  ?>
