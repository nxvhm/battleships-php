<?php

namespace App;
use App\GridObject;

class Grid {

  const TILE_NO_SHOT  = ' . ';
  const TILE_MISS     = ' - ';
  const TILE_HIT      = ' X ';
  const TILE_SHIP     = ' S ';
  const TILE_EMPTY    = '   ';
  /**
   * Grid Name used as Session key
   *
   * @var String
   */
  public $name;
  /**
   * Available Rows in Grid (as Letters)
   *
   * @var Array
   */
  public $rows;
  /**
   * Availavle Columns in Grid (as integers)
   *
   * @var Array
   */
  public $cols;
  /**
   * Objects Drawn on Grid
   *
   * @var Array Array Containing App\GridObject Objects
   */
  public $objects = [];
  /**
   * Grid Shown to player
   *
   * @var array
   */
  public $grid;

  /**
   * @param   String  $name  Used for session name
   */
  public function __construct(string $name) {
    $this->name = $name;
    $this->rows = self::getRowsArray();
    $this->cols = self::getColumnsArray();
  }

  /**
   * Populate Grid Array
   * @return Array
   */
  public function init() {

    $this->grid = [];

    foreach ($this->rows as $rowKey => $rowLetter) {
      foreach ($this->cols as $colKey => $colNumber) {
        $this->grid[$rowLetter][$colNumber] = $this->getGridTile();
      }
    }

    return $this->grid;

  }

  /**
   * Return Column Numbers as preformatted text
   * @return  String
   */
  public function renderColumns () {
    $cols = '';

    foreach (self::getColumnsArray() as $key => $num) {
        if ($key == 0) $cols .= "   ";

        $cols .= " $num ";
    }

    return $cols;
  }

  /**
   * Return grid as string containing preformatted text
   * @param   Array $grid THe Grid Array to be printed out
   * @return  String $gridOutput Print to browser in <pre> tags to keep preformatted text
   */
  public static function render(array $grid) {

    $gridOutput = '';
    $rows = self::getRowsArray();
    $cols = self::getColumnsArray();

    foreach ($rows as $rowKey => $rowLetter) {
      foreach($cols as $colKey => $colNumber) {

        if ($colKey == 0) {
            $gridOutput .= "\n $rowLetter ";
        }

        $gridOutput .= $grid[$rowLetter][$colNumber];
      }
    }

    return $gridOutput;
  }

  /**
   * Add Object to Grid Array
   * @param   App\GridObject  $object with filled data
   * @return  App\GridObject
   */
  public function drawObject(GridObject $object) {

    if (count($object->coords) < $object->squares) {
      throw new Exception('Invalid Grid Object. Not enough points');
    }

    foreach($object->coords as $key => $pair) {
      list($rowLetter, $colNumber) = $pair;

      $this->grid[$rowLetter][$colNumber] = Grid::TILE_SHIP;
    }

    array_push($this->objects, $object);

    return $object;
  }

  /**
   * Get Default Grid Tile According the Grid Name
   * @return  String
   */
  public function getGridTile(): string {
    $tile = '';
    switch ($this->name) {
      case 'playergrid':
        $tile = Grid::TILE_NO_SHOT;
        break;
      case 'shipsgrid':
        $tile =  Grid::TILE_EMPTY;
        break;
      default:
        $tile = Grid::TILE_NO_SHOT;
        break;
    }
    return $tile;
  }

  /**
   * Check if a grid point contains Ship tile
   * If true, then the point is occupied
   * @param   String   $rowLetter
   * @param   Integer  $col
   * @throws  InvalidArgumentException If $rowLetter/$col are out of range
   * @return  Boolean
   */
  public function isPointOccupied(string $rowLetter,int $col): bool {
    if (!isset($this->grid[$rowLetter])) {
      throw new \InvalidArgumentException("Invalid Row Letter. $rowLetter Does Not exist");
    }

    if (!isset($this->grid[$rowLetter][$col])) {
      throw new \InvalidArgumentException("Invalid Column Num. $col Does Not exist");
    }

    $tile = $this->grid[$rowLetter][$col];
    return $tile === Grid::TILE_SHIP;
  }

  /**
   * [getFreeCoords description]
   *
   * @param  Integer $align  1 for ALIGN_VERTICAL, 2 for ALIGN_HORIZONTAL
   * @param  Integer $squares  Number of squares to check
   *
   * @return  array            [return description]
   */
  public function getFreeCoords($align, $squares): array {

      # Generate randon column and row.
      $columnStart = rand(0, 9);
      $rowStart = rand(0, 9);

      # Make sure grid has enough columns to draw the squares
      if ($align == GridObject::ALIGN_HORIZONTAL) {
        while ($columnStart + $squares > 10) {
          $columnStart = rand(0, 9);
        }
      }

      # Make sure grid has enough rows to draw the squares
      if ($align == GridObject::ALIGN_VERTICAL) {
        while ($rowStart + $squares > 10) {
          $rowStart = rand(0, 9);
        }
      }

      // echo "\n". $this->rows[$rowStart].$this->cols[$columnStart]." \n ";
      # Generate Coordinates Array
      $coords = $this->calculateFreeCoordinates($rowStart, $columnStart, $squares, $align);
      # If free coordinates are less then squares required, repeat the procedure
      while (count($coords) < $squares) {
        $coords = $this->calculateFreeCoordinates($rowStart, $columnStart, $squares, $align);
      }

      return $coords;
  }

  /**
   * Check for series of free points/squares in the grid
   * Point is considered free if no TILE_SHIP on it
   * @param   Integer  &$rowStart    Array key from $this->rows
   * @param   Integer  &$columnStart Array key from $this->cols
   * @param   Integer  $squares      The required number of free points/squares
   * @param   Integer  $alignment    1 for vertical, 2 for horizontal
   * @return  array                 [return description]
   */
  public function calculateFreeCoordinates(&$rowStart, &$columnStart, $squares, $alignment): array {
    $coords = [];

    $row    = $this->rows[$rowStart];
    $column = $this->cols[$columnStart];


    if ($alignment == GridObject::ALIGN_HORIZONTAL) {
      # For Horizontal Coordinates we change the column index
      if ($columnStart == 9) {
        $columnStart = 0;
      }

      for ($i=0; $i < $squares; $i++) {
        $nextColumnKey = $columnStart + $i;
        $column = isset($this->cols[$nextColumnKey]) ? $this->cols[$nextColumnKey] : false;
        if (!$column) {$columnStart = 0; break;}
        // echo $column;

        if (!$this->isPointOccupied($row, $column)) {
          # If point is free push it in coords array
          array_push($coords, [$row, $column]);
        } else {
          # Change column if path occupied
          $columnStart++;
          break;
        }
      }
      return $coords;

    } else {
      # For Vertical Coordinates we change the row index
      if ($rowStart == 9) {
        $rowStart = 0;
      }

      for ($i=0; $i < $squares; $i++) {
        $nextRowKey = $rowStart + $i;
        $row = isset($this->rows[$nextRowKey]) ? $this->rows[$nextRowKey] : false;
        if (!$row) {$rowStart = 0; break;}
        // echo $row;
        if (!$this->isPointOccupied($row, $column)) {
          # If point is free push it in coords array
          array_push($coords, [$row, $column]);
        } else {
          # Change Row if path occupied
          $rowStart++;
          break;
        }
      }
      return $coords;

    }
  }

  public function getObjectsCoordinates(): array {
    $objectsCoords = [];
    if(count($this->objects)) {
      foreach($this->objects as $obj) {
        foreach($obj->coords as $pair) {
          $point = $pair[0].$pair[1];
          $objectsCoords[$point] = $obj;
        }
      }
    }

    return $objectsCoords;
  }

  /**
   * Get Array with Letters from A to J for Row Letters
   * @return  Array
   */
  public static function getRowsArray(): array {
    return range('A', 'J');
  }

  /**
   * Get Array with numbers from 1 to 10 used for column numers
   * @return Array
   */
  public static function getColumnsArray(): array {
      return range(1, 10);
  }
}
