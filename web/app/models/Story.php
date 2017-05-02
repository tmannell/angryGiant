<?php

\Base::instance()->set('DB',new DB\SQL(\Base::instance()->get('sqliteDB')));

class Story extends \DB\SQL\Mapper {

  public function __construct() {
    parent::__construct( \Base::instance()->get('DB'), 'stories');
  }

  public function listByTitle() {
    return $this->select('id, title', null, array('order' => 'title ASC'));
  }

}