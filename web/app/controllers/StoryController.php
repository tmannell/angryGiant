<?php

Class StoryController extends Controller {

  function viewStory() {
    $story = new Story();
    $story->load(['id = ?'], Helper::explodePath(2));

    $this->assign('story', $story);
    $this->display('viewStory.tpl');
  }

  function addStory() {

  }

  function editStory() {

  }

  function deleteStory() {

  }
}