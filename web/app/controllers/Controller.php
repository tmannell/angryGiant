<?php

/**
 * Class Controller
 */
class Controller extends Smarty {

  /**
   * @var static
   *  Base instance of fatfree.
   */
  protected $f3;
  /**
   * @var \DB\SQL
   *  DB instance
   */
  protected $db;

  protected $fullStory;

  /**
   * Controller constructor.
   *
   * Creates fatfree base instance and initial db connection.
   * Sets specialized smarty configuration.
   *
   * Controller class is our base class all other classes inherit.
   */
  public function __construct() {
    parent::__construct();
    // Set up f3 base
    $this->f3 = Base::instance();
    // Set up db connection
    $this->db = new \DB\SQL($this->f3->get('sqliteDB'));

    // Configure smarty.
    $this
      ->setTemplateDir ($this->f3->get('smartyTemplateDir'))
      ->setCompileDir  ($this->f3->get('smartyCompileDir'))
      ->setCacheDir    ($this->f3->get('smartyCacheDir'));

    $this->caching = false;       // set Smarty caching off by default

    $this->fullStory = new DB\SQL\MAPPER($this->db, 'fullStory');
  }

  /**
   *  Do stuff before we route somewhere.
   */
  public function beforeroute() {
    // Assign some global smarty vars

    $this->assign('siteName', 'Angry Giant');
  }

  /**
   *  Do stuff after we route somewhere.
   */
  public function afterroute() {
    // Clear SESSION.<msg>
    $this->removeSessionMessage();
  }

  /**
   * Retrieves Authorization status.
   * @return string
   */
  public function getAuthorizationStatus() {
    $uid = $this->f3->get('SESSION.uid');
    if ($uid == 1) {
      return 'admin';
    }
    else {
      return isset($uid) ? 'authorized' : 'anonymous';
    }
  }

  /**
   * Starts user session.
   */
  public function startSession() {
    // Before we start session just make sure the DB exists.
    $result = $this->db->exec('SELECT name FROM sqlite_master WHERE type="table" AND name="users"');
    // If it does create teh session
    if (!empty($result)) {
      new \DB\SQL\Session($this->db, 'sessions', TRUE);
    }
    // Otherwise go to the install path.
    elseif (empty($result) && $this->f3->get('PATH') != '/install') {
      $this->f3->reroute('/install');
    }

  }

  /**
   * Remove Session Message
   *  Clear any session messages that are in the session var.
   */
  public function removeSessionMessage() {
    $session = $this->f3->get('SESSION');

    if ($session['repeat'] == false) {
      foreach ($session as $key => $value) {
        if ($key == 'success' || $key == 'warning' || $key == 'error') {
          $this->f3->clear("SESSION.$key");
        }
      }
    }
  }

  /**
   * Check user access to all routes as defined in config.ini.
   */
  public function userAccess() {

    $access = Access::instance();
    $uid = $this->f3->get('SESSION.uid');
    if ($uid == 1) {
      $user_status = 'admin';
    }
    else {
      $user_status = isset($uid) ? 'authorized' : 'anonymous';
    }
    $access->authorize($user_status);
  }
}