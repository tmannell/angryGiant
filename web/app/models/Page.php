<?php

// Set DB instance.
\Base::instance()->set('DB',new DB\SQL(\Base::instance()->get('sqliteDB')));

class Page extends \DB\SQL\Mapper {

  /**
   * Page constructor.
   */
  public function __construct() {
    parent::__construct( \Base::instance()->get('DB'), 'pages');
  }

  /**
   * Lists all the page numbers in the story
   *
   * @param $sid
   * @return object
   */
  public function listPageNumbersInStory($sid) {
    return $this->select('page_number', ['story_id = ?', $sid], ['order' => 'page_number ASC']);
  }

  public function allPages($sid, $order) {
    return $this->select('*', ['story_id = ?', $sid], ['order' => implode(' ', $order)]);
  }
}