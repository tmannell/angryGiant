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

  public function startSession() {
    $result = $this->db->exec('SELECT name FROM sqlite_master WHERE type="table" AND name="users"');
    if (!empty($result)) {
      $db = new \DB\SQL($this->f3->get('sqliteDB'));
      new \DB\SQL\Session($db, 'sessions', TRUE);
    }
    elseif (empty($result) && $this->f3->get('PATH') != '/install') {
      $this->f3->reroute('/install');
    }

  }

  public function checkAuthorization() {

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