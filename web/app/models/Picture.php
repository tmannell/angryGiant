<?php

// Set DB instance.
\Base::instance()->set('DB',new DB\SQL(\Base::instance()->get('sqliteDB')));

/**
 * Class Picture
 */
class Picture extends \DB\SQL\Mapper {

  /**
   * Picture constructor.
   */
  public function __construct() {
    parent::__construct( \Base::instance()->get('DB'), 'pictures');
  }

}