<?php

\Base::instance()->set('DB',new DB\SQL(\Base::instance()->get('sqliteDB')));

class Page extends \DB\SQL\Mapper {

  public function __construct() {
    parent::__construct( \Base::instance()->get('DB'), 'pages');
  }

  public function listPageNumbersInStory($sid) {
    return $this->select('page_number', ['story_id = ?', $sid], ['order' => 'page_number ASC']);
  }
}