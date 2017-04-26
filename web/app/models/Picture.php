<?php

\Base::instance()->set('DB',new DB\SQL(\Base::instance()->get('sqliteDB')));

class Picture extends \DB\SQL\Mapper {

  public function __construct() {
    parent::__construct( \Base::instance()->get('DB'), 'pictures');
  }

}