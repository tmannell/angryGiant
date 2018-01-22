<?php

class MainController extends Controller {

  function home($f3) {
//    $this->assign('siteName', 'AngryGiant');
//    $story = new Story();
//    $story->load(['published = ?', 1], ['order' => 'id DESC', 'limit' => 1]);
//
//    $sc = new StoryController($story->id);
//    $sc->viewStories($story->id);
//    $picture = new Picture();
//    $picture->load(['id = ?', $story->picture_id]);
//    $page = $this->db->exec('SELECT MIN(page_number) as firstPage FROM pages WHERE story_id = ?', $story->id);
//
//    $this->assign('story', $story);
//    $this->assign('role', $this->getAuthorizationStatus());
//    $this->assign('contentTitle', 'Latest: ' . $story->title);
//    $this->assign('pageTitle', $story->title);
//    $this->assign('firstPage', $page[0]['firstPage']);
//    $this->assign('filename', $picture->filename);
    $this->display('Home.tpl');
  }
}