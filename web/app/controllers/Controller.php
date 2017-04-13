<?php

class Controller {

  protected $f3;
  protected $db;

  public function beforeroute() {

  }

  public function afterroute() {

  }

  public function __construct() {
    $this->f3 = Base::instance();
    $this->db = new \DB\SQL($this->f3->get('sqliteDB'));
  }
}