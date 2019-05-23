<?php
namespace App;

abstract class GridObject {

  const ALIGN_VERTICAL = 1;

  const ALIGN_HORIZONTAL = 2;

  /**
   * Array with coordinates of the squares
   *
   * @var Array
   */
  public $coords = [];
  /**
   * Number of squares the object takes on grid
   * @var Integer
   */
  public $squares;

}
