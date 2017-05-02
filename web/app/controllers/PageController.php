<?php

class PageController extends Controller {

  private $pageWeight;
  private $shortTitle;

  function __construct() {
    parent::__construct();

    $this->pageWeight = Helper::explodePath(2);
    $this->shortTitle = Helper::explodePath(1);
  }
}