<?php

namespace App;

class GameSession {
  /**
   * Save All Session items under this namespace
   * @var String
   */
  public static $sessionKey = 'game';

  /**
   * Save Grid Array in Session
   * @param   String  $name  Grid Name
   * @param   Array   $grid   The Grid Array
   * @return  void
   */
  public static function setGrid($name, $grid) {
    $_SESSION[self::$sessionKey]['grids'][$name] = $grid;
  }

  /**
   * Get Grid Array from Session
   * @param   String  $name  The name of grid to be fetched
   * @return  Mixed False for none-existing grid or grid Array
   */
  public static function getGrid($name) {
    return isset($_SESSION[self::$sessionKey]['grids'][$name])
      ? $_SESSION[self::$sessionKey]['grids'][$name]
      : false;
  }
  /**
   * Set Item in Game Session
   * @param   String  $key    The name to use for the resouce
   * @param   Mixed  $value   Objects/Arrays to save
   * @return  void
   */
  public function setItem($key, $value) {
    $_SESSION[self::$sessionKey][$key] = $value;
  }
  /**
   * Get item from game session
   * @param   String  $key    Item session key
   * @return  Mixed
   */
  public function getItem($key) {
    return $_SESSION[self::$sessionKey][$key];
  }
  /**
   * Game actions counter
   * @return  void
   */
  public static function addAction() {
    $actions = $_SESSION[self::$sessionKey]['actions'] ?? 0;
    $actions++;
    $_SESSION[self::$sessionKey]['actions'] = $actions;
  }
  /**
   * Get current actions count
   * @return  Integer
   */
  public static function getAction() {
    return $_SESSION[self::$sessionKey]['actions'] ?? 0;
  }


}
