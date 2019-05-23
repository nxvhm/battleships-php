<?php

namespace App;

use App\Grid;
use App\Ship;
use App\GameSession;

class Game {
  /**
   * The playergrid object
   *
   * @var App\Grid
   */
  public $playergrid;
  /**
   * The shipsgrid object
   *
   * @var App\Grid
   */
  public $shipsgrid;

  /**
   * Contains the last user input/action
   *
   * @var String
   */
  public $input;

  /**
   * The status after last input/action process
   *
   * @var String
   */
  public $actionResult;

  public function __construct() {
    # Display player grid with no battleships on it
    $this->playergrid = new Grid('playergrid');
    # Display battleships Grid for 'show' cmd
    $this->shipsgrid  = new Grid('shipsgrid');
  }

  /**
   * Generate Ship Object and Add it to grid
   * @param  Int Number of squares for the ship. 5 for Battleship, 4 for Destroyer
   * @return App\Ship
   */
  public function generateShip($squares = 5) {

    $ship = new Ship();
    $ship->align = rand(1, 2);
    $ship->squares = $squares;
    $ship->coords = $this->shipsgrid->getFreeCoords($ship->align, $ship->squares);

    return $this->shipsgrid->drawObject($ship);

  }

  /**
   * Render Shipsgrid if SHOW requested
   * Render playergrid by default
   * @return  String Grid in preformatted text
   */
  public function renderGrid() {

    $gridName = $this->input && $this->input === 'SHOW'
      ? $this->shipsgrid->name
      : $this->playergrid->name;

    return Grid::render(GameSession::getGrid($gridName));

  }

  /**
   * If User Input detected, validated and process
   * @return  Mixed NULL for no input or the input data
   */
  public function listenForInput() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $this->input = $this->validateInput(isset($_POST['coord']) ? $_POST['coord'] : false);

      if ($this->input && $this->input !== 'SHOW') {
        $this->processInput();
      }

      return ($this->input);
    }

    return null;
  }

  /**
   * Validate User Input
   * @param   String  $input The provided input
   * @return  Mixed   False for invalid input, the input string otherwise
   */
  private function validateInput($input) {

    if (!$input || !is_string($input)) {
      return false;
    }

    $input = strtoupper($input);
    $lnt = strlen($input);

    return ( ($lnt < 2 || $lnt > 3) && $input != 'SHOW')
      ? false
      : $input;

  }

  private function processInput() {
    try {
      list ($rowLetter, $colNum) = [$this->input[0], $this->input[1]];

      $colNum = isset($this->input[2]) ? $colNum.$this->input[2] : $colNum;

      if ($this->shipsgrid->isPointOccupied($rowLetter, $colNum)) {
        # if point occupied then action is a hit
        $this->registerHit($rowLetter, $colNum);
      } else {
        # If no point occupied on ships grid then action is a miss
        $this->registerMiss($rowLetter, $colNum);
      }
      # Count Player Action
      GameSession::addAction();

    } catch (\InvalidArgumentException $e) {
      $this->input = false;
    } catch (Exception $e) {
      $this->input = false;
    }
  }

  /**
   * Start Game
   * Init grids and objects and Save them in Session
   * Or load them from Session if game started
   * @return  void
   */
  public function start() {

    if (!GameSession::getGrid($this->playergrid->name)) {
      # Initialize Empty Grid
      $this->playergrid->init();
      # Save Player Grid To Session
      GameSession::setGrid($this->playergrid->name, $this->playergrid->grid);
    } else {
      # Load Grid
      $this->playergrid->grid = GameSession::getGrid($this->playergrid->name);
    }

    if (!GameSession::getGrid($this->shipsgrid->name)) {
      # Initialize Empty Grid
      $this->shipsgrid->init();
      # Generate 1xBattleship and 2xDestroyers
      $ships = [
        $this->generateShip(5),
        $this->generateShip(4),
        $this->generateShip(4)
      ];
      # Save Ships Grid To Session
      GameSession::setGrid($this->shipsgrid->name, $this->shipsgrid->grid);
      # Save Ships Array to Session
      GameSession::setItem('ships', $ships);
      # Save Array with All the ships coordinate points
      GameSession::setItem('ships_coordinates', $this->shipsgrid->getObjectsCoordinates());

    } else {
      # Load Grid
      $this->shipsgrid->grid = GameSession::getGrid($this->shipsgrid->name);
      # If game started, fetch ships from session and assign them to grid
      $this->shipsgrid->objects = GameSession::getItem('ships');
    }

  }


  /**
   * Register Hit Action on grids.
   * Update game session
   * @param   String  $rowLetter
   * @param   Integer $colNum
   * @return  void
   */
  private function registerHit($rowLetter, $colNum) {

    # Update shipsgrid with empty tile on it
    $this->shipsgrid->grid[$rowLetter][$colNum] = Grid::TILE_EMPTY;

    # Update player grid with hit tile
    $this->playergrid->grid[$rowLetter][$colNum] = Grid::TILE_HIT;

    # Save updated grids in session
    GameSession::setGrid($this->shipsgrid->name, $this->shipsgrid->grid);
    GameSession::setGrid($this->playergrid->name, $this->playergrid->grid);

    # Remove Ship Coordinate from Session
    $coords = GameSession::getItem('ships_coordinates');
    unset($coords[$rowLetter.$colNum]);

    # Update ship_coordinates in session
    GameSession::setItem('ships_coordinates', $coords);

    $sunk = $this->hasShipSunk($rowLetter, $colNum);
    $this->actionResult = $sunk === false
      ? "HIT AT $rowLetter$colNum"
      : "SHIP SUNK";
  }

  /**
   * Register Miss Action on grids.
   * Update game session
   * @param   String  $rowLetter
   * @param   Integer $colNum
   * @return  void
   */
  private function registerMiss($rowLetter, $colNum) {
    # Update playergrid with miss tile
    $tile = $this->playergrid->grid[$rowLetter][$colNum];

    # Update player grid with miss only if no hit on that point
    if ($tile !== Grid::TILE_HIT) {
      $this->playergrid->grid[$rowLetter][$colNum] = Grid::TILE_MISS;
    }

    # Save updated player gridin session
    GameSession::setGrid($this->playergrid->name, $this->playergrid->grid);
    $this->actionResult = "MISS AT $rowLetter$colNum";
  }

  /**
   * @param   String  $rowLetter  Ship's row coordinate
   * @param   Integer  $colNum    Ship's col coordinate
   * @return  Boolean
   */
  private function hasShipSunk($rowLetter, $colNum) {
    $sunk = false;

    # Get current ship objects
    $ships = GameSession::getItem('ships');

    # Remove coordinate point from ship's coords and check if sunk
    foreach($ships as $key => $ship) {
      $oldCoordsCount = count($ship->coords);
      if ($oldCoordsCount === 0) continue;

      $ship->coords = Ship::removePointFromCoordinates($ship->coords, $rowLetter, $colNum);
      if ($oldCoordsCount > 0 && count($ship->coords) === 0) {
        $sunk = true;
      }

      $ships[$key] = $ship;
    }

    # Update ships objects in session
    GameSession::setItem('ships', $ships);
    return $sunk;
  }

  /**
   * Check if no coordinates left in ships coordinates session
   * @return  Boolean
   */
  public function isFinished() {
    $coordsCount = count(GameSession::getItem('ships_coordinates'));
    return $coordsCount === 0;
  }
}
