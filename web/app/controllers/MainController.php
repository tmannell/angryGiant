<?php

class MainController extends Controller {

  function home($f3) {


    $story = new Story();
    $story->load(['published = ?', 1], ['order' => 'id DESC', 'limit' => 1]);

    $this->assign('latestStoryST', $story->short_title);
    $this->display('Home.tpl');
  }
}