<?$appname=$_GET['appname'];$appid=$_GET['appid'];?>
<div id="<?echo $appname.$appid;?>" class="flappyblock" style="background-color:#1b1b1b; height:600px; color:#f2f2f2; width:1000px; border-radius:0px 0px 5px 5px; overflow:hidden;">

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
<canvas id="animation" style="width: 100%;height: 100%;"></canvas>

  <script>
  /*

  FLAPPY BLOCK

  Press s to start the game.

  Space: Flap wings

  */

  var c = document.getElementById("animation");
  var ctx = c.getContext("2d");

  var WIDTH = window.innerWidth;
  var HEIGHT = window.innerHeight;

  c.width = WIDTH;
  c.height = HEIGHT;

  function JVector(x, y) {
  	this.x = x;
  	this.y = y;
  }

  JVector.prototype.add = function(vector) {
  	this.x += vector.x;
  	this.y += vector.y;
  }

  JVector.prototype.mult = function(scalar) {
  	this.x *= scalar;
  	this.y *= scalar;
  }

  JVector.prototype.div = function(scalar) {
  	this.x /= scalar;
  	this.y /= scalar;
  }

  JVector.prototype.mag = function() {
  	return Math.sqrt((this.x * this.x) + (this.y * this.y));
  }

  JVector.prototype.normalize = function() {
  	var m = this.mag();
  	this.div(m);
  }

  function Bird(posVec, velVec, accVec, size, maxSpeed, flapStrength) {
  	this.pos = posVec;
  	this.vel = velVec;
  	this.acc = accVec;

  	this.size = size;

  	this.maxSpeed = maxSpeed;

  	this.flapStrength = flapStrength;

  	this.flapping = false;
  }

  Bird.prototype.flap = function() {
  	this.vel = new JVector(this.vel.x, -this.flapStrength);
  }

  Bird.prototype.reset = function() {
  	this.pos = new JVector(0, HEIGHT / 2);
  	this.vel = new JVector(0, 0);
  }

  Bird.prototype.update = function() {
  	this.vel.add(this.acc);
  	this.pos.add(this.vel);
  	if (this.vel.mag() >= this.maxSpeed) {
  		this.vel.normalize();
  		this.vel.mult(this.maxSpeed);
  	}
  }

  Bird.prototype.display = function() {
  	ctx.fillStyle = "#fff";
  	ctx.fillRect(this.pos.x - this.size, this.pos.y - this.size, this.size, this.size);
  	if (!this.flapping) {
  		ctx.beginPath();
  		ctx.moveTo(this.pos.x - this.size, this.pos.y - this.size);
  		ctx.lineTo(this.pos.x - this.size / 2, this.pos.y - this.size - this.size);
  		ctx.lineTo(this.pos.x, this.pos.y - this.size);
  		ctx.closePath;
  	}
  	if (this.flapping) {
  		ctx.beginPath();
  		ctx.moveTo(this.pos.x - this.size, this.pos.y);
  		ctx.lineTo(this.pos.x - this.size / 2, this.pos.y + this.size);
  		ctx.lineTo(this.pos.x, this.pos.y);
  		ctx.closePath;
  	}
  	ctx.strokeStyle = "#fff";
  	ctx.stroke();
  }

  Bird.prototype.checkWorld = function() {
  	var x = this.pos.x;
  	var y = this.pos.y;
  	if (x <= 0) {
  		this.pos.x = WIDTH;
  	} else if (x > WIDTH) {
  		this.pos.x = 0;
  	}
  	if (y <= 0) {
  		this.pos.y = 0 + this.size;
  	} else if (y > HEIGHT) {
  		this.pos.y = HEIGHT;
  	}
  }

  Bird.prototype.frame = function() {
  	this.update();
  	this.checkWorld();
  	this.display();
  }

  function Obstacle(x) {
  	this.x = x;
  	this.height = (Math.random() * 2 - 1) * (HEIGHT / 2);
  	this.width = Math.random() * 50 + 20;
  }

  Obstacle.prototype.display = function() {
  	ctx.fillStyle = "#fff";
  	if (this.height >= 0) {
  		ctx.fillRect(this.x - this.width / 2, 0, this.width, this.height);
  	} else if (this.height < 0) {
  		ctx.fillRect(this.x - this.width / 2, HEIGHT + this.height, this.width, HEIGHT);
  	}
  	// console.log(this.x + ", " + this.height + ", " + this.width);
  }

  Obstacle.prototype.detectCollision = function(player) {
  	var playerX = player.pos.x;
  	var playerY = player.pos.y;

  	if (this.height >= 0) {
  		if (playerX >= this.x - this.width / 2 && playerX <= this.x + this.width && playerY <= this.height) {
  			console.log("Player Hit!");
  			return true;
  		}
  	} else if (this.height < 0) {
  		if (playerX >= this.x - this.width / 2 && playerX <= this.x + this.width && playerY >= HEIGHT + this.height) {
  			console.log("Player Hit!");
  			return true;
  		}
  	}
  	return false;
  }

  function ObstacleManager(maxNum) {
  	this.maxNum = maxNum;
  	this.obstacles = [];

  	this.generateObstacles = function() {
  		this.obstacles = [];
  		for (var i = 0; i < this.maxNum; i++) {
  			var xPos = ((i * WIDTH / this.maxNum) + Math.floor(Math.random() * 50)) + WIDTH / 4;
  			this.obstacles.push(new Obstacle(xPos));
  		}
  	}

  	this.renderObstacles = function() {
  		for (var i = 0; i < this.obstacles.length; i++) {
  			this.obstacles[i].display();
  		}
  	}

  	this.detectCollisions = function(player) {
  		for (var i = 0; i < this.obstacles.length; i++) {
  			var collision = this.obstacles[i].detectCollision(player);
  			if (collision) {
  				return true;
  			}
  		}
  	}
  }

  function Score() {
  	this.score = 0;

  	this.increment = function() {
  		this.score += 100;
  		console.log(this.score);
  	}

  	this.reset = function() {
  		this.score = 0;
  	}

  	this.display = function() {
  		ctx.fillStyle = "#fff";
  		ctx.font = "20px Oswald";
  		ctx.textAlign = "left";
  		ctx.fillText("Score: " + this.score, 15, 35);
  	}
  }

  var bg = "#000";

  var Game = function() {
  	this.startScreen = true;
  	this.playing = false;
  	this.gameOver = false;

  	this.locVec = new JVector(0, HEIGHT / 2);
  	this.velVec = new JVector(0, 0);
  	this.accVec = new JVector(0.1, 0.4);

  	this.score = new Score();

  	this.player = new Bird(this.locVec, this.velVec, this.accVec, 20, 12, 10);

  	this.obstacleManager = new ObstacleManager(5);

  	this.gravity = new JVector(0, .6);

  	this.startGame = function() {
  		this.startScreen = false;
  		this.playing = true;
  		this.gameOver = false;
  		this.obstacleManager.generateObstacles();
  		this.player.reset();
  	}

  	this.killPlayer = function() {
  		this.startScreen = false;
  		this.playing = false;
  		this.gameOver = true;
  	}

  	this.draw = function() {
  		ctx.fillStyle = bg;
  		ctx.fillRect(0, 0, WIDTH, HEIGHT);

  		if (this.startScreen) {
  			ctx.strokeStyle = "#fff";
  			ctx.fillStyle = "#fff";
  			ctx.font = "48px Oswald";
  			ctx.textAlign = "center";
  			ctx.strokeText("Flappy Block", WIDTH / 2, HEIGHT / 2);
  			ctx.font = "18px Oswald";
  			ctx.fillText("Hit 'S' to start the game!", WIDTH / 2, HEIGHT / 2 + 40);
  			this.score.reset();
  		}

  		if (this.playing) {
  			this.score.display();
  			this.player.vel.add(this.gravity);
  			this.obstacleManager.renderObstacles();
  			var collision = this.obstacleManager.detectCollisions(this.player);
  			if (collision) {
  				this.killPlayer();
  			}
  			this.player.frame();
  			if (this.player.pos.x == 0) {
  				this.obstacleManager.generateObstacles();
  				this.score.increment();
  			}
  		}

  		if (this.gameOver) {
  			ctx.strokeStyle = "#fff";
  			ctx.fillStyle = "#fff";
  			ctx.font = "48px Oswald";
  			ctx.textAlign = "center";
  			ctx.strokeText("Gameover!", WIDTH / 2, HEIGHT / 2);
  			ctx.font= "24px Oswald";
  			ctx.fillText("Final Score: " + this.score.score, WIDTH / 2, HEIGHT / 2 + 40);
  			ctx.font = "18px Oswald";
  			ctx.fillText("Hit 'S' to play again!", WIDTH / 2, HEIGHT / 2 + 80);
  		}

  		requestAnimationFrame(this.draw.bind(this));
  	}
  }

  var flappyBlock = new Game();
  flappyBlock.draw();

  window.addEventListener("resize", function() {
  	WIDTH = window.innerWidth;
  	HEIGHT = window.innerHeight;

  	c.width = WIDTH;
  	c.height = HEIGHT;
  });

  document.onkeydown = keyDown;
  document.onkeyup = keyUp;

  function keyDown(e) {
  	var event = e || window.event;
  	var key = event.keyCode;
  	console.log(key);

  	if (key == 83 && !flappyBlock.playing) {
  		flappyBlock.startGame();
  		flappyBlock.score.reset();
  	}

  	if (key == 32 && !flappyBlock.player.flapping && flappyBlock.playing) {
  		flappyBlock.player.flap();
  		flappyBlock.player.flapping = true;
  	}
  }

  function keyUp(e) {
  	var event = e || window.event;
  	var key = event.keyCode;
  	console.log(key);

  	if (key == 32 && flappyBlock.player.flapping) {
  		flappyBlock.player.flapping = false;
  	}
  }

  function lerp(current, target, percent) {
  	return (target - current) * percent;
  }

  </script>

</div>
