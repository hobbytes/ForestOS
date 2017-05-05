<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" class="brokensgame" style="background-color:#1b1b1b; height:100%; color:#f2f2f2; width:100%; border-radius:0px 0px 5px 5px; overflow:hidden;">
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
/* colors */
.a, .a-x {background: #573659;}
.b, .b-x {background: #ad4375;}
.c, .c-x {background: #fa7370;}
.d, .d-x {background: #f59231;}
.e, .e-x {background: #fecd5f;}
.f, .f-x {background: #9ccf5e;}
.g, .g-x {background: #3cad5b;}
.h, .h-x {background: #36cbbf;}
.i, .i-x {background: #1d839c;}
.j, .j-x {background: #2f506c;}

.controls {
  display: flex;
  justify-content: space-between;
  user-select: none;
  padding: 1em 0;
}

#board {
  display: flex;
  flex-flow: row wrap;
  height: 70vmin;
	width: 70vmin;
  border: 1ch solid;
  border-radius: .3em;
}

#board > * {
  flex: 0 1 7vmin;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 7vmin;
  transition: background 300ms linear;
}

#board:not(.started) > *:first-of-type::after {
  content: '★';
}

#colors {
  display: flex;
  justify-content: space-between;
  margin-top: 1ch;
}

#colors > * {
  flex: 0 1 7vmin;
	height: 7vmin;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: .3em;
}

.new-game {
  pointer-events: auto;
  cursor: pointer;
  text-decoration: underline;
  color: #00bcd4;
}

#game-over {
  pointer-events: none;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 3em;
}


.brokensgame {
  margin: 0;
  font-size: calc(1em + 1vmin);
  font-family: Helvetica, FontAwesome,   sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #212429; /*#353539;*/
  color: #fffced;
}
</style>

<main>
  <div class="controls">
    <div class="new-game">Новая игра</div>
    <div>Ход <span class="moves">0</span> / <span class="total">35</span></div>
  </div>
  <div id="board"></div>
  <div id="colors"></div>
  <div id="game-over"></div>
</main>


<script>
var board = document.querySelector('#board')
var colors = document.querySelector('#colors')
var gameover = document.querySelector('#game-over')
var tally = document.querySelector('.moves')
var total = document.querySelector('.total')

var colorArray = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j']

var running = false

var cell = '-x'
var skill = 7
var moves = 0
var cap = 40
var color

var shuffle = function(collection) {
  for (var i = collection.length; i; i--) {
    var j = Math.floor(Math.random() * i);
    [collection[i - 1], collection[j]] = [collection[j], collection[i - 1]];
  }
  return collection
}

var setColors = function(collection, n) {
  console.log(collection)
  return n < 10 ? shuffle(collection).slice(0, n) : collection
}

var checkWin = function(moves) {
  let n = 0
  let msg = ''
  if (moves <= cap) {
    if (board.childNodes[99].className.indexOf(cell) > -1) {
      for (var i = 0; i < 100; i++) {
        if (board.childNodes[i].className.indexOf(cell) > -1) {
          n++
        }
      }
    }

    if (n === 100) {
      msg = '<span class="new-game">Ты выиграл!</span>'
      running = false
    } else if (n < 100 && moves >= cap) {
      msg = '<span class="new-game">Упс! попробуй еще...</span>'
      running = false
    }
  }
  if(!running) {
    gameover.innerHTML = msg
  }
}

var checkColor = function(color) {
  var tiles = board.childNodes
  for(var x = 0; x < 100; x++) {
    if(tiles[x].className.indexOf(cell) > -1) {
      tiles[x].className = color + cell
      if (x + 1 < 100 && tiles[x + 1].className === color) {
        tiles[x + 1].className += cell
      }
      if (x + 10 < 100 && tiles[x + 10].className === color) {
        tiles[x + 10].className += cell
      }
      if (x - 1 >= 0 && x % 10 > 0 && tiles[x - 1].className === color) {
        tiles[x - 1].className += cell
      }
      if (x - 10 >= 0 && x % 10 > 0 && tiles[x - 10].className === color) {
        tiles[x - 10].className += cell
      }
    }
  }
}

var builder = function(container, element, collection, count, randomize) {
  container.innerHTML = ''
  count = count || collection.length
  randomize = randomize || false
  for (var i = 0; i < count; i++) {
    var child = document.createElement(element)
    child.className = randomize ? collection[Math.floor((Math.random() * collection.length))] : collection[i]
    container.appendChild(child)
  }
}

var newGame = function() {
  var options = setColors(colorArray.slice(), skill)
  console.log(options)
  moves = 0
  tally.innerText = moves
  total.innerText = cap
  gameover.innerHTML = ''
  running = true
  builder(colors, 'chip', options)
  builder(board, 'tile', options, 100, true)
  color = board.childNodes[0].className
  board.className = ''
  board.childNodes[0].className = color + cell
  checkColor(color)
}

var play = function(chip) {
  if (running && color !== chip){
    color = chip
    if(board.className !== 'started') {
      board.className = 'started'
    }
    moves++
    tally.innerText = moves
    checkColor(chip)
    checkWin(moves)
  }
}

document.addEventListener("DOMContentLoaded", function() {
  newGame()
}, false)

document.addEventListener('click', function(event) {
  var css = Array.from(event.target.classList)
  console.log(event.target.tagName)
  if(event.target.tagName === 'CHIP') {
    play(event.target.className)
  }
  else if(css.includes('new-game')) {
    newGame()
  }
})

</script>
</div>
