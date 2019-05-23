<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Grid;
use App\GridObject;
use App\Ship;

class GridTest extends TestCase
{

  public function testInitGrid() {
    $grid = new Grid('testgrid');
    $grid->init();
    $rowsCount = count($grid->rows);
    $this->assertTrue((count($grid->grid) == $rowsCount));
  }

  public function testRenderGrid() {
    $grid = new Grid('testgrid');
    $grid->init();
    $output = Grid::render($grid->grid);
    $this->assertTrue(strlen($output) > 100);
  }

  public function testDrawObject() {
    $grid = new Grid('testgrid');
    $grid->init();
    $ship = new Ship();
    $ship->align = rand(1, 2);
    $ship->squares = rand(4, 5);
    $ship->coords = $grid->getFreeCoords($ship->align, $ship->squares);
    $grid->drawObject($ship);
    $this->assertTrue(count($grid->objects) === 1);
  }

  public function testIsGridPointOccupied() {
    $grid = new Grid('testgrid');
    $grid->init();
    $grid->grid['A']['5'] = Grid::TILE_SHIP;
    $this->assertTrue($grid->isPointOccupied('A', '5'));
  }
}
