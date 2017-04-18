<?php

class Controller {

  protected $f3;
  protected $db;

  public function __construct() {
    $this->f3 = Base::instance();
    $this->db = new \DB\SQL($this->f3->get('sqliteDB'));
  }

  public function beforeroute() {

  }

  public function afterroute() {

  }

  public function getAuthorizationStatus() {
    $uid = \Base::instance()->get('SESSION.uid');
    if ($uid == 1) {
      return 'admin';
    }
    else {
      return isset($uid) ? 'authorized' : 'anonymous';
    }
  }

  static function checkAuthorization() {

    $access = Access::instance();
    $uid = \Base::instance()->get('SESSION.uid');
    if ($uid == 1) {
      $user_status = 'admin';
    }
    else {
      $user_status = isset($uid) ? 'authorized' : 'anonymous';
    }
    $access->authorize($user_status);
  }
}