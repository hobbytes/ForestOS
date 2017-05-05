<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" class="minesweeper" style="background-color:#1b1b1b; height:100%; color:#f2f2f2; width:100%; border-radius:0px 0px 5px 5px; overflow:hidden;">
<?php
/*Console*/
//Подключаем библиотеки

//Инициализируем переменные
$click=$_GET['mobile'];
$appdownload=$_GET['appdownload'];
$type=$_GET['type'];
$folder=$_GET['destination'];
$text=preg_replace('#%u([0-9A-F]{4})#se','iconv("UTF-16BE","UTF-8",pack("H4","$1"))',$_GET["command"]);
//Логика
?>

<style>
.minesweeper{
  font-family:Arial;
  height:100%;
  background:#fdfdfd;
  background-image: -webkit-linear-gradient(#cdcdcd, #dfdfdf);
  background-image: -moz-linear-gradient(#cdcdcd, #dfdfdf);
  background-image: -o-linear-gradient(#cdcdcd, #dfdfdf);
  background-image: linear-gradient(#cdcdcd, #dfdfdf);
}

h1,h4{
  text-align:center;
}

h1{
  margin-top:30px;
  margin-bottom:0px;
  padding-bottom:0px;
}

h4{
  font-size:13px;
  color:#666;
  margin-top:5px;
  padding-top:0px;
}

#container{
  width:200px;
  margin:20px auto;
}

#container:after {
   content: ".";
   visibility: hidden;
   display: block;
   height: 0;
   clear: both;
}

.box{
  display:block;
  float:left;
  width:20px;
  height:20px;
  background:#333;
  border: 1px solid #222;
  border-radius: 2px;
  background-image: -webkit-linear-gradient(top, #666, #333);
  background-image: -moz-linear-gradient(top, #666, #333);
  background-image: -o-linear-gradient(top, #666, #333);
  background-image: -ms-linear-gradient(top, #666, #333);
  background-image: linear-gradient(to bottom, #666, #333);
  -webkit-box-shadow: 0 0 5px rgba(0,0,0,0.75);
  -moz-box-shadow: 0 0 5px rgba(0,0,0,0.75);
  box-shadow: 0 0 5px rgba(0,0,0,0.75);
  text-align:center;
  line-height:20px;
  font-size:12px;
  font-weight:bold;
  color:#ff2e2e;
}

.box:hover{
  cursor:pointer;
  background:#777;
}

span.opened{
  background:#777;
}

span.checked,span.checked:hover{
background-image: -webkit-linear-gradient(top, #ba0000, #610000);
background-image: -moz-linear-gradient(top, #ba0000, #610000);
background-image: -o-linear-gradient(top, #ba0000, #610000);
background-image: -ms-linear-gradient(top, #ba0000, #610000);
background-image: linear-gradient(to bottom, #ba0000, #610000);
}

span.bomb{
  display:inline-block;
  width:10px;
  height:10px;
  margin-top:4px;
  border:1px solid #ffea00;
  border-radius:5px;
  background:#f00303;
}

a#again{
  clear:both;
  display:block !important;
  text-decoration:none;
  background:#333;
  width:200px;
  height:50px;
  text-align:center;
  line-height:50px;
  font-size:13px;
  color:#fff;
  font-weight:bold;
  border-radius:5px;
  margin:auto;
}
</style>
<div id="container"></div>
<script>
//variables
var lvl1w = 9;
var lvl1h = 9;
var lvl1m = 10;

var mineField;
var opened;

startGame();
function startGame(){
  mineField = new Array();
  opened = 0;

  //creating on array
  for(var i=0; i<lvl1h; i++){
    mineField[i] = new Array();
    for(var j=0; j<lvl1w; j++){
      mineField[i].push(0);
    }
  }

  //placing mines
  var placedMines = 0;
  var randomRow,randomCol;
  while(placedMines < lvl1m){
    randomRow = Math.floor(Math.random() * lvl1h);
    randomCol = Math.floor(Math.random() * lvl1w);
    if(mineField[randomRow][randomCol] == 0){
      mineField[randomRow][randomCol] = 9;
      placedMines++;
    }
  }

  //placing digits
  for(var i=0; i < lvl1h; i++){
    for(var j=0; j<lvl1w; j++){
      if(mineField[i][j] == 9){
        for(var ii=-1; ii<=1; ii++){
          for(var jj=-1; jj<=1; jj++){
            if(ii!=0 || jj!=0){
              if(tileValue(i+ii,j+jj) != 9 && tileValue(i+ii,j+jj) != -1){
                mineField[i+ii][j+jj]++;
              }
            }
          }
        }
      }
    }
  }

  //placing in page
  for(var i=0; i<lvl1h; i++){
    for(var j=0; j<lvl1w; j++){
      var tile = $("#container").append("<span id='"+i+""+j+"' data-row='"+i+"' data-col='"+j+"' class='box first'></span>");
    }
  }

  $("#container span.box").on('contextmenu',function(e){
    e.preventDefault();
    if($(this).hasClass("checked")){
      $(this).removeClass("checked");
    } else {
      $(this).addClass("checked");
    }
  });

  $("#container span.box").click(function(){
    if(!$(this).hasClass('checked')){
    var tile = $(this);
    var clickedRow = tile.data('row');
    var clickedCol = tile.data('col');
    var clickedVal = mineField[clickedRow][clickedCol];

    if(clickedVal == 0){
      floodFill(clickedRow,clickedCol);
    }

    if(clickedVal > 0 && clickedVal < 9){
      tile.removeClass('first');
      tile.html(clickedVal);
      opened++;
    }

    if(clickedVal == 9){
      tile.removeClass('first');
      tile.append("<span class='bomb'></span>");
      $("#container").after('<a href="#" id="again">Ты проиграл! Еше раз?</a>');
      $("#container .box").off('click');
      $("a#again").on('click',function(e){
        e.preventDefault();
        $("#container span.box").remove();
        $("#again").remove();
        startGame();
      });
    }

    checkopened();
    }
  });

}

function floodFill(row,col){
  var tile = $("#container span#"+row+""+col);
  if(tile.hasClass('first')){
    tile.removeClass('first');
    if(tile.hasClass("checked")){
        tile.removeClass("checked");
      }
    if(mineField[row][col] > 0){
      tile.html(mineField[row][col]);
      opened++;
    } else {
      tile.addClass("opened");
      opened++;
    }

    if(mineField[row][col] == 0){
      for(var ii=-1; ii<=1; ii++){
        for(var jj=-1; jj<=1; jj++){
          if(ii!=0 || jj!=0){
            if(tileValue(row+ii,col+jj) != 9){
              if(tileValue(row+ii,col+jj) != -1){
                floodFill(row+ii,col+jj);
              }
            }
          }
        }
      }
    }
  }
}

function checkopened(){
  console.log(opened);
  if(opened >= 71){
    $("#container").after('<a href="#" id="again">Ты выйграл! Еше раз?</a>');
    $("#container .box").off('click');
    $("a#again").on('click',function(e){
      e.preventDefault();
      $("#container span.box").remove();
      $("#again").remove();
      startGame();
    });
  }
}

function tileValue(row,col){
  if(mineField[row] == undefined || mineField[row][col] == undefined){
    return -1;
  } else {
    return mineField[row][col];
  }
}
</script>
</div>
