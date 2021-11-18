<canvas id="canvas" width="600" height="400" tabindex="1"></canvas>

<style>
    #canvas {
    width: 600px;
    height: 400px;
    border: 1px solid black;
  }

</style>

<script>
// Collect The Square game

// Get a reference to the canvas DOM element
var canvas = document.getElementById('canvas');
// Get the canvas drawing context
var context = canvas.getContext('2d');

// Your score
var score = 0;

// Properties for your square
var x = 50; // X position
var y = 100; // Y position
var speed = 6; // Distance to move each frame
var sideLength = 50; // Length of each side of the square

// Enemy
var enemyX = 500; // X position
var enemyY = 300; // Y position
var enemyspeed = 2; // Distance to move each frame
var enemySideLength = 50; // Length of each side of the square

// FLags to track which keys are pressed
var down = false;
var up = false;
var right = false;
var left = false;

// Properties for the target square
var targetX = 0;
var targetY = 0;
var targetX2 = 0;
var targetY2 = 0;
var targetX3 = 0;
var targetY3 = 0;
var targetLength = 25;

// Determine if number a is within the range b to c (exclusive)
function isWithin(a, b, c) {
  return (a > b && a < c);
}

// Countdown timer (in seconds)
var countdown = 30;
// ID to track the setTimeout
var id = null;

// Listen for keydown events
canvas.addEventListener('keydown', function(event) {
  event.preventDefault();
  console.log(event.key, event.keyCode);
  if (event.keyCode === 40) { // DOWN
    down = true;
  }
  if (event.keyCode === 38) { // UP
    up = true;
  }
  if (event.keyCode === 37) { // LEFT
    left = true;
  }
  if (event.keyCode === 39) { // RIGHT
    right = true;
  }
});

// Listen for keyup events
canvas.addEventListener('keyup', function(event) {
  event.preventDefault();
  console.log(event.key, event.keyCode);
  if (event.keyCode === 40) { // DOWN
    down = false;
  }
  if (event.keyCode === 38) { // UP
    up = false;
  }
  if (event.keyCode === 37) { // LEFT
    left = false;
  }
  if (event.keyCode === 39) { // RIGHT
    right = false;
  }
});

// Show the start menu
function menu() {
  erase();
  context.fillStyle = '#000000';
  context.font = '36px Arial';
  context.textAlign = 'center';
  context.fillText('Collect the Square!', canvas.width / 2, canvas.height / 4);
  context.font = '24px Arial';
  context.textAlign = 'center';
  context.fillStyle = '#00FF00';
  context.fillText('Green Square = 1 point', canvas.width / 2, canvas.height / 2.5);
  context.fillStyle = '#00F7FF';
  context.fillText('Blue Square = 2 points', canvas.width / 2, canvas.height / 2.1);
  context.fillStyle = '#FFDD00';
  context.fillText('Gold Square = 3 points', canvas.width / 2, canvas.height / 1.8);
  context.font = '24px Arial';
  context.fillStyle = '#000000';
  context.fillText('Avoid the black square enemy!', canvas.width / 2, canvas.height / 1.55);
  context.fillText('Click to Start', canvas.width / 2, canvas.height / 1.35);
  context.font = '18px Arial'
  context.fillText('Use the arrow keys to move', canvas.width / 2, (canvas.height / 3.5) * 3);
  // Start the game on a click
  canvas.addEventListener('click', startGame);
}
// Start the game
function startGame() {
	// Reduce the countdown timer ever second
  id = setInterval(function() {
    countdown--;
  }, 1000)
  // Stop listening for click events
  canvas.removeEventListener('click', startGame);
  // Put the target at a random starting point
	moveTarget();
  // Kick off the draw loop
  draw();
}

// Show the game over screen
function endGame() {
	// Stop the countdown
  clearInterval(id);
  // Display the final score
  erase();
  context.fillStyle = '#000000';
  context.font = '24px Arial';
  context.textAlign = 'center';
  context.fillText('Final Score: ' + score, canvas.width / 2, canvas.height / 2);
}

// Move the target square to a random position
function moveTarget() {
  // 1 pt block
  targetX = Math.round(Math.random() * canvas.width - targetLength);
  targetY = Math.round(Math.random() * canvas.height - targetLength);
  // 2 pt block
  targetX2 = Math.round(Math.random() * canvas.width - targetLength);
  targetY2 = Math.round(Math.random() * canvas.height - targetLength);
  // 3 pt block
  targetX3 = Math.round(Math.random() * canvas.width - targetLength);
  targetY3 = Math.round(Math.random() * canvas.height - targetLength);
}


// Clear the canvas
function erase() {
  context.fillStyle = '#FFFFFF';
  context.fillRect(0, 0, 600, 400);
}

// The main draw loop
function draw() {
  erase();
  // Move the square
  if (down) {
    y += speed;
  }
  if (up) {
    y -= speed;
  }
  if (right) {
    x += speed;
  }
  if (left) {
    x -= speed;
  }
  // Keep the square within the bounds
  if (y + sideLength > canvas.height) {
    y = canvas.height - sideLength;
  }
  if (y < 0) {
    y = 0;
  }
  if (x < 0) {
    x = 0;
  }
  if (x + sideLength > canvas.width) {
    x = canvas.width - sideLength;
  }
  // Moves enemy towards player
  if (x > enemyX) {
    enemyX += enemyspeed;
  }
  if (x < enemyX) {
    enemyX -= enemyspeed;
  }
  if (y > enemyY) {
    enemyY += enemyspeed;
  }
  if (y< enemyY) {
    enemyY -= enemyspeed;
  }

  // Collide with the target
  // 1 pt block collision
  if (isWithin(targetX, x, x + sideLength) || isWithin(targetX + targetLength, x, x + sideLength)) { // X
    if (isWithin(targetY, y, y + sideLength) || isWithin(targetY + targetLength, y, y + sideLength)) { // Y
      // Respawn the target
      moveTarget();
      // Increase the score
      score++;
    }
  }
  // 2 pt block collision
  if (isWithin(targetX2, x, x + sideLength) || isWithin(targetX2 + targetLength, x, x + sideLength)) { // X
    if (isWithin(targetY2, y, y + sideLength) || isWithin(targetY2 + targetLength, y, y + sideLength)) { // Y
      // Respawn the target
      moveTarget();
      // Increase the score
      score += 2;
    }
  }
  // 3 pt block collision
  if (isWithin(targetX3, x, x + sideLength) || isWithin(targetX3 + targetLength, x, x + sideLength)) { // X
    if (isWithin(targetY3, y, y + sideLength) || isWithin(targetY3 + targetLength, y, y + sideLength)) { // Y
      // Respawn the target
      moveTarget();
      // Increase the score
      score += 3;
    }
  }
  // Enemy collision
  if (isWithin(enemyX, x, x + sideLength) || isWithin(enemyX+ targetLength, x, x + sideLength)) { // X
    if (isWithin(enemyY, y, y + sideLength) || isWithin(enemyY + targetLength, y, y + sideLength)) { // Y
      countdown = 0;
    }
  }

  // Draw the square
  context.fillStyle = '#FF0000';
  context.fillRect(x, y, sideLength, sideLength);
  // Draw the target 
  context.fillStyle = '#000000';
  context.fillRect(enemyX, enemyY, enemySideLength, enemySideLength);
  //draw 1 pt block
  context.fillStyle = '#00FF00';
  context.fillRect(targetX, targetY, targetLength, targetLength);

  //draw 2 pt block
  context.fillStyle = '#00F7FF';
  context.fillRect(targetX2, targetY2, targetLength, targetLength);

  //draw 3 pt block
  context.fillStyle = '#FFDD00';
  context.fillRect(targetX3, targetY3, targetLength, targetLength);

  // Draw the score and time remaining
  context.fillStyle = '#000000';
  context.font = '24px Arial';
  context.textAlign = 'left';
  context.fillText('Score: ' + score, 10, 24);
  context.fillText('Time Remaining: ' + countdown, 10, 50);
  // End the game or keep playing
  if (countdown <= 0) {
    endGame();
  } else {
    window.requestAnimationFrame(draw);
  }
}

// Start the game
menu();
canvas.focus();
</script>