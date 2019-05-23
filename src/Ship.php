<?php
namespace App;
use App\GridObject;

class Ship extends GridObject {

  /**
   * Remove Coordinate point from coordinates array
   * @param   Array   $coords
   * @param   String  $rowLetter  The Row Letter
   * @param   Int     $col        The Column Integer
   * @return  Array
   */
  public static function removePointFromCoordinates($coords, $rowLetter, $colNum):array {
    if (!count($coords)) {
      return false;
    }

    foreach($coords as $key => $pair) {
      list($shipRow,$shipCol) = $pair;
      if ($shipRow == $rowLetter && $shipCol == $colNum) {
        unset($coords[$key]);
      }
    }

    return $coords;

  }

}
