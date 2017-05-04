<?php

// Set DB instance.
\Base::instance()->set('DB',new DB\SQL(\Base::instance()->get('sqliteDB')));

/**
 * Class User
 */
class User extends \DB\SQL\Mapper {

  /**
   * User constructor.
   */
  public function __construct() {
    parent::__construct( \Base::instance()->get('DB'), 'users');
  }
}