<?php

require_once('vendor/autoload.php');

use App\Game;
use App\Ship;
use App\GameSession;

session_start();

$game = new Game;
$game->start();
$input = $game->listenForInput();

if($game->isFinished()) {
  session_destroy();
  echo "Well done! You completed the game in ". GameSession::getAction() . "shots.";
  echo "<br /><a href='/index.php'>Try again</a>";
  return;
}


if ($input === false) {
  echo "<pre>\n *** ERROR *** \n</pre>";
}

if ($game->actionResult && strlen($game->actionResult)) {
  echo "<pre>\n $game->actionResult \n</pre>";
}
?>

<html>
<pre>
<?= $game->playergrid->renderColumns(); ?>
<?= $game->renderGrid(); ?>
</pre>


  <p>
    <form name="input" action="index.php" method="post">
      Enter coordinates (row, col), e.g. A5
      <input type="input" size="5" name="coord" autocomplete="off" autofocus="">
      <input type="submit">
    </form>
  </p>


<pre>
  <?php
  $i = 1;
  foreach(GameSession::getItem('ships_coordinates') as $point => $ship) {
    echo "\n $i  $point";
    $i++;
  }
  ?>
</pre>

<pre>
  <?php
  $i = 1;
  foreach(GameSession::getItem('ships') as $key => $ship) {
    echo "\n $i ".  count($ship->coords);
    foreach($ship->coords as $key => $pair) {
      list($row,$col) = $pair;
      echo $row.$col.",";
    }
    // var_dump($ship->coords);
    $i++;
  }
  ?>
</pre>

</html>
