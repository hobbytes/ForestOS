<?
/*--------Получаем App Name и App ID--------*/
$appname=$_GET['appname'];
$appid=$_GET['appid'];
session_start();
$language_themecreate  = parse_ini_file('app.lang');
?>
<div id="<?echo $appname.$appid;?>" style="background-color:#f2f2f2; max-height:100%; height:500px; width:100%; padding-top:10px; border-radius:0px 0px 5px 5px; overflow:auto;">
<?php
/*--------Подключаем библиотеки--------*/
include '../../core/library/etc/security.php';
include '../../core/library/gui.php';
/*--------Проверяем безопасность--------*/
$security	=	new security;
$security->appprepare();
/*
Инициализируем переменные
$click - переменная используется для определения действия (клик или прикосновение)
$folder - переменная хранит место запуска программы
*/
$click=$_GET['mobile'];
$folder=$_GET['destination'];
$newgui = new gui;
/*--------Запускаем сессию--------*/
session_start();
/*--------Логика--------*/
$name = $_GET['name'];
if(!empty($name)){
  $apply_status = $_GET['status'];
  $topbarbackcolor = $_GET['topbarbackcolor'].';';
  $topbarfontcolor = $_GET['topbarfontcolor'].';';

  $menubackcolor = $_GET['menubackcolor'].';';
  $menufontcolor = $_GET['menufontcolor'].';';

  $draggablebackcolor = $_GET['draggablebackcolor'].';';
  $draggablefontcolor = $_GET['draggablefontcolor'].';';

  $backgroundcolor = $_GET['backgroundcolor'].';';
  $backgroundfontcolor = $_GET['backgroundfontcolor'].';';

$theme_content = "
[Info]\n
Name='$name'\n
[topbar]\n
topbarbackcolor='$topbarbackcolor'\n
topbarfontcolor='$topbarfontcolor'\n
[menu]\n
menubackcolor='$menubackcolor'\n
menufontcolor='$menufontcolor'\n
[window]\n
draggablebackcolor='$draggablebackcolor'\n
draggablefontcolor='$draggablefontcolor'\n
[background]\n
backgroundcolor='$backgroundcolor'\n
backgroundfontcolor='$backgroundfontcolor'\n
  ";

  $filename = str_replace(' ','',$name);
  file_put_contents('../../core/design/themes/'.$filename.'.fth',$theme_content);
  if($apply_status=='1'){
    file_put_contents('../../users/'.$_SESSION['loginuser'].'/settings/etc/theme.fth',$theme_content);
  }
}
?>
<style>

.color-picker {
  margin: 100px auto;
  background-color: #d6d6d6;
  padding: 10px 20px;
  border: 1px solid #4a4a4a;
}
.color-picker .sliders {
  float: left;
}
.color-picker .color-preview {
  border-radius: 50%;
  float: right;
}
.bar{
  background-color: #d6d6d6;
}
.rgba-picker {
  width: 165px;
  margin: 20px auto;
  height: 95px;
}
.rgba-picker .sliders {
  width: 80px;
  float: left;
}
.rgba-picker .color-preview {
  width: 60px;
  height: 60px;
  margin-top: 10px;
  border-radius: 50%;
  float: right;
  position: relative;
  overflow: hidden;
  background-image: url('https://res.cloudinary.com/lemurxx/image/upload/v1473765062/checked_cagv6w.png');
}
.rgba-picker div.color {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
}
.red-slider input[type=range]::-webkit-slider-runnable-track {
  background: #444;
}
.red-slider input[type=range]::-webkit-slider-thumb {
  background: #ff3300;
}
.red-slider input[type=range]:focus::-webkit-slider-runnable-track {
  background: #444;
}
.red-slider input[type=range]::-moz-range-track {
  background: #444;
}
.red-slider input[type=range]::-moz-range-thumb {
  background: #ff3300;
}
.red-slider input[type=range]::-ms-fill-lower {
  background: #444;
}
.red-slider input[type=range]::-ms-fill-upper {
  background: #444;
}
.red-slider input[type=range]::-ms-thumb {
  background: #ff3300;
}
.red-slider input[type=range]:focus::-ms-fill-lower {
  background: #444;
}
.red-slider input[type=range]:focus::-ms-fill-upper {
  background: #444;
}
.green-slider input[type=range]::-webkit-slider-runnable-track {
  background: #444;
}
.green-slider input[type=range]::-webkit-slider-thumb {
  background: #33cc33;
}
.green-slider input[type=range]:focus::-webkit-slider-runnable-track {
  background: #444;
}
.green-slider input[type=range]::-moz-range-track {
  background: #444;
}
.green-slider input[type=range]::-moz-range-thumb {
  background: #33cc33;
}
.green-slider input[type=range]::-ms-fill-lower {
  background: #444;
}
.green-slider input[type=range]::-ms-fill-upper {
  background: #444;
}
.green-slider input[type=range]::-ms-thumb {
  background: #33cc33;
}
.green-slider input[type=range]:focus::-ms-fill-lower {
  background: #444;
}
.green-slider input[type=range]:focus::-ms-fill-upper {
  background: #444;
}
.blue-slider input[type=range]::-webkit-slider-runnable-track {
  background: #444;
}
.blue-slider input[type=range]::-webkit-slider-thumb {
  background: #336699;
}
.blue-slider input[type=range]:focus::-webkit-slider-runnable-track {
  background: #444;
}
.blue-slider input[type=range]::-moz-range-track {
  background: #444;
}
.blue-slider input[type=range]::-moz-range-thumb {
  background: #336699;
}
.blue-slider input[type=range]::-ms-fill-lower {
  background: #444;
}
.blue-slider input[type=range]::-ms-fill-upper {
  background: #444;
}
.blue-slider input[type=range]::-ms-thumb {
  background: #336699;
}
.blue-slider input[type=range]:focus::-ms-fill-lower {
  background: #444;
}
.blue-slider input[type=range]:focus::-ms-fill-upper {
  background: #444;
}
.silver-slider input[type=range]::-webkit-slider-runnable-track {
  background: #444;
}
.silver-slider input[type=range]::-webkit-slider-thumb {
  background: silver;
}
.silver-slider input[type=range]:focus::-webkit-slider-runnable-track {
  background: #444;
}
.silver-slider input[type=range]::-moz-range-track {
  background: #444;
}
.silver-slider input[type=range]::-moz-range-thumb {
  background: silver;
}
.silver-slider input[type=range]::-ms-fill-lower {
  background: #444;
}
.silver-slider input[type=range]::-ms-fill-upper {
  background: #444;
}
.silver-slider input[type=range]::-ms-thumb {
  background: silver;
}
.silver-slider input[type=range]:focus::-ms-fill-lower {
  background: #444;
}
.silver-slider input[type=range]:focus::-ms-fill-upper {
  background: #444;
}
.short-slider {
  margin: 10px auto;
}
.short-slider input[type=range] {
  -webkit-appearance: none;
  width: 80px;
  padding: 0;
}
.short-slider input[type=range]::-webkit-slider-runnable-track {
  width: 80px;
  height: 2px;
  margin:3px;
  border: none;
}
.short-slider input[type=range]::-webkit-slider-thumb {
  -webkit-appearance: none;
  border: none;
  height: 10px;
  width: 10px;
  border-radius: 50%;
  margin-top: -4px;
  cursor: pointer;
}
.short-slider input[type=range]:focus {
  outline: none;
}
.short-slider input[type=range]::-moz-range-track {
  width: 80px;
  height: 2px;
  border: none;
}
.short-slider input[type=range]::-moz-range-thumb {
  border: none;
  height: 10px;
  width: 10px;
  border-radius: 50%;
  cursor: pointer;
}
.short-slider input[type=range]::-ms-fill-lower {
  border-radius: 10px;
}
.short-slider input[type=range]::-ms-fill-upper {
  border-radius: 10px;
}
.short-slider input[type=range]::-ms-thumb {
  border: none;
  height: 10px;
  width: 10px;
  margin-top: -1px;
  cursor: pointer;
}
.short-slider input[type=range]::-ms-track {
  width: 80px;
  height: 4px;
  background: transparent;
  border-color: transparent;
  border-width: 2px 0;
  overflow: visible;
  color: transparent;
}
.theme_block{
  padding: 10px;
  border-bottom: 1px solid #c1c1c1;
}
.ui-forest-button{
  margin:5px;
}
</style>

<div>
  <?

  echo '<div style="text-align:left; margin-top:10px; margin-left:10px;"><b style="font-size:20px;">'.$language_themecreate[$_SESSION['locale'].'_tc_label'].'</b></div>';

  echo '<div id="namethemblock" class="theme_block">';
  echo "<div>".$language_themecreate[$_SESSION['locale'].'_tc_name_label'].":</div>";
  $newgui->inputslabel('', 'text', ''.$appid.'themename', '','100', $language_themecreate[$_SESSION['locale'].'_tc_name_label']);
  echo '</div>';

  echo '<div id="topbarthemblock" class="theme_block">';
  echo "<div>".$language_themecreate[$_SESSION['locale'].'_tc_tb_label'].":</div>";
  echo '<div id="theme_background" selector="topbarthemblock" object="topbartheme" type="background-color" onClick="theme_button'.$appid.'(this);" class="ui-forest-button ui-forest-accept">'.$language_themecreate[$_SESSION['locale'].'_tc_tb_colorbtn'].'</div>';
  echo '<div id="theme_fontcolor" selector="topbarthemblock" object="topbartheme" type="color" onClick="theme_button'.$appid.'(this);" class="ui-forest-button ui-forest-accept">'.$language_themecreate[$_SESSION['locale'].'_tc_tb_textcolorbtn'].'</div>';
  echo '</div>';

  echo '<div id="windowthemblock" class="theme_block">';
  echo "<div>".$language_themecreate[$_SESSION['locale'].'_tc_w_label'].":</div>";
  echo '<div id="theme_backgroundwindow" selector="windowthemblock" object="dragwindow" type="background-color" onClick="theme_button'.$appid.'(this);" class="ui-forest-button ui-forest-accept">'.$language_themecreate[$_SESSION['locale'].'_tc_w_colorbtn'].'</div>';
  echo '<div id="theme_fontcolorwindow" selector="windowthemblock" object="dragwindow" type="color" onClick="theme_button'.$appid.'(this);" class="ui-forest-button ui-forest-accept">'.$language_themecreate[$_SESSION['locale'].'_tc_w_textcolorbtn'].'</div>';
  echo '</div>';

  echo '<div id="menuthemblock" class="theme_block">';
  echo "<div>".$language_themecreate[$_SESSION['locale'].'_tc_m_label'].":</div>";
  echo '<div id="theme_backgroundmenu" selector="menuthemblock" object="menutheme" type="background-color" onClick="theme_button'.$appid.'(this);" class="ui-forest-button ui-forest-accept">'.$language_themecreate[$_SESSION['locale'].'_tc_m_colorbtn'].'</div>';
  echo '<div id="theme_fontcolormenu" selector="menuthemblock" object="menutheme" type="color" onClick="theme_button'.$appid.'(this);" class="ui-forest-button ui-forest-accept">'.$language_themecreate[$_SESSION['locale'].'_tc_m_textcolorbtn'].'</div>';
  echo '</div>';

  echo '<div id="wallthemblock" class="theme_block">';
  echo "<div>".$language_themecreate[$_SESSION['locale'].'_tc_d_label'].":</div>";
  echo '<div id="theme_wall" selector="wallthemblock" object="backgroundtheme" type="background-color" onClick="theme_button'.$appid.'(this);" class="ui-forest-button ui-forest-accept">'.$language_themecreate[$_SESSION['locale'].'_tc_d_colorbtn'].'</div>';
  echo '<div id="theme_link" selector="wallthemblock" object="linktheme" type="color" onClick="theme_button'.$appid.'(this);" class="ui-forest-button ui-forest-accept ui-forest-center">'.$language_themecreate[$_SESSION['locale'].'_tc_d_textcolorbtn'].'</div>';
  echo '</div>';
  echo '<div onClick="theme_save'.$appid.'(0);" class="ui-forest-button ui-forest-cancel">'.$language_themecreate[$_SESSION['locale'].'_button_save'].'</div>';
  echo '<div onClick="theme_save'.$appid.'(1);" class="ui-forest-button ui-forest-cancel">'.$language_themecreate[$_SESSION['locale'].'_tc_saveandbtn'].'</div>';
  ?>
</div>

<div id="picker<?echo $appid?>" class="rgba-picker color-picker" style="display:none;">
  <div class="sliders">
    <div class="red-slider short-slider">
      <input class="bar" type="range" id="rangeinput" value="250" max="255" onchange="setRgba()" oninput="setRgba()" />
    </div>
    <div class="green-slider short-slider">
      <input class="bar" type="range" id="rangeinput" value="50" max="255" onchange="setRgba()" oninput="setRgba()" />
    </div>
    <div class="blue-slider short-slider">
      <input class="bar" type="range" id="rangeinput" value="50" max="255" oninput="setRgba()" onchange="setRgba()" />
    </div>
    <div class="silver-slider short-slider">
     <input class="bar" type="range" id="rangeinput" value="80" max="100" oninput="setRgba()" onchange="setRgba()" />
    </div>
  </div>
  <div class="color-preview">
    <div class="color"></div>
  </div>
</div>
</div>
<script>
/*--------Логика JS--------*/
  var obj = '';
  var type  = '';
  var selector  = '';
 function theme_button<?echo $appid?>(object){
   var _obj = object.id;
   obj  = $("#"+_obj).attr('object');
   type = $("#"+_obj).attr('type');
   selector = $("#"+_obj).attr('selector');
   $("#picker<?echo $appid?>" ).css('display','block');
   $("#picker<?echo $appid?>" ).appendTo("#"+selector);
   var obj_color = $('.'+obj).css(type);
   channel  = obj_color.replace(/[rgba\(\)]/gi,'').split(',');
   var _alpha = parseInt(channel[3]);
   if(!_alpha){
     _alpha='1';
   }
   $('.red-slider').children('input').val(parseInt(channel[0]));
   $('.green-slider').children('input').val(parseInt(channel[1]));
   $('.blue-slider').children('input').val(parseInt(channel[2]));
   $('.silver-slider').children('input').val(_alpha * 100);

   document.querySelector(".rgba-picker .color-preview .color").style.backgroundColor = obj_color;
   //console.log($('.green-slider').children('input').val() + "|" + channel[1]);
  }

function setRgba () {
  //var obj = '.'+window.objectaction;
  var red = document.querySelector('.rgba-picker .red-slider input').value;
  var green = document.querySelector('.rgba-picker .green-slider input').value;
  var blue = document.querySelector('.rgba-picker .blue-slider input').value;
  var alpha = document.querySelector('.rgba-picker .silver-slider input').value / 100;
  var color = "rgba(" + red + "," + green + "," + blue + "," + alpha + ")";
  document.querySelector(".rgba-picker .color-preview .color").style.backgroundColor = color;

  if(obj!=''){
      $("#aboutmenu").css('display','none');
      $("."+obj).css(type,color);
      if(obj=='dragwindow' && type == 'background-color'){
        $(".windowborder").css('border','3px solid '+color);
        $(".menulines").css('background-color',color);
        $("#aboutmenu").css('display','block');
      }
      if(obj=='menutheme'){
        $("#aboutmenu").css('display','block');
      }
  }
}

function theme_save<?echo $appid?>(status){
  var themename = $('.<?echo $appid?>themename').val();
  if(themename){
    var topbarbackcolor = $('.topbartheme').css('background-color');
    var topbarfontcolor = $('.topbartheme').css('color');

    var menubackcolor = $('.menutheme').css('background-color');
    var menufontcolor = $('.menutheme').css('color');

    var draggablebackcolor = $('.dragwindow').css('background-color');
    var draggablefontcolor = $('.dragwindow').css('color');

    var backgroundcolor = $('.backgroundtheme').css('background-color');
    var backgroundfontcolor = $('.linktheme').css('color');

    $("#<?echo $appid;?>").load("<?echo $folder?>themecreator.php?name="+escape(themename)+"&status="+status+"&menubackcolor="+escape(menubackcolor)+"&menufontcolor="+escape(menufontcolor)+"&topbarbackcolor="+escape(topbarbackcolor)+"&topbarfontcolor="+escape(topbarfontcolor)+"&draggablebackcolor="+escape(draggablebackcolor)+"&draggablefontcolor="+escape(draggablefontcolor)+"&backgroundcolor="+escape(backgroundcolor)+"&backgroundfontcolor="+escape(backgroundfontcolor)+"&id=<?echo rand(0,10000).'&appname='.$appname.'&destination='.$folder.'&appid='.$appid;?>");
  }else{
    $('.<?echo $appid?>themename').focus();
  }
}
</script>
<?
unset($appid);//Очищаем переменную $appid
?>
