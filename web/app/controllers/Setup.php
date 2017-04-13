<?php

class Setup {

  protected $f3;
  protected $db;

  function installCheck() {
    $this->f3 = $f3 = Base::instance();
    $db_path = preg_replace('/sqlite\:/', '', $f3->get('sqliteDB'));
    if (!file_exists($db_path)) {
      echo 'Please create sqlite database before running install script.';
      return;
    }

    $this->db = $db = new \DB\SQL($f3->get('sqliteDB'));
    $f3->set('result', $db->exec('SELECT name FROM sqlite_master WHERE type="table" AND name="users"'));

    if (empty($f3->get('result')) && !isset($_POST['create'])) {
      $this->installSetupForm();
    }
    elseif (empty($f3->get('result')) && $_POST['create'] == TRUE) {
      $this->install();
    }
  }

  function installSetupForm() {
    $form = new HTML_QuickForm();
    $form->addElement('text', 'admin_username', 'Admin Username:');
    $form->addElement('password', 'admin_password', 'Admin Password:');

    $this->f3->set('form', $form);

    echo \Template::instance()->render('installSetupForm.htm');
  }

  function install() {
    $db = $this->db;

    $db->begin();
    // Create users table.
    $db->exec(
      "CREATE TABLE IF NOT EXISTS users (
          id       INTEGER PRIMARY KEY AUTOINCREMENT,
          username TEXT NOT NULL,
          password TEXT NOT NULL)");
    // Create pictures table.
    $db->exec(
      "CREATE TABLE IF NOT EXISTS pictures (
          id   INTEGER PRIMARY KEY AUTOINCREMENT,
          path TEXT NOT NULL)");
    $db->exec("CREATE INDEX picture_path_idx ON pictures (PATH)");
    // Create stories table.
    $db->exec(
      "CREATE TABLE IF NOT EXISTS stories (
          id         INTEGER PRIMARY KEY AUTOINCREMENT,
          title      TEXT NOT NULL,
          created_by INTEGER NOT NULL,
          picture_id INTEGER NOT NULL,
          CONSTRAINT FK_story_user_id
            FOREIGN KEY (created_by)
            REFERENCES users (id)
              ON DELETE NO ACTION
              ON UPDATE NO ACTION,
          CONSTRAINT FK_story_picture_id
            FOREIGN KEY (picture_id)
            REFERENCES pictures (id)
              ON DELETE CASCADE 
              ON UPDATE CASCADE)");
    //Create pages table.
    $db->exec(
      "CREATE TABLE IF NOT EXISTS pages (
          id          INTEGER PRIMARY KEY AUTOINCREMENT,
          description TEXT NULL,
          picture_id  INTEGER NOT NULL,
          story_id    INTEGER NULL,
          created_by  INTEGER NOT NULL,
          CONSTRAINT FK_page_user_id
            FOREIGN KEY (created_by)
            REFERENCES users (id)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT FK_page_picture_id
            FOREIGN KEY (picture_id)
            REFERENCES pictures (id)
            ON DELETE CASCADE 
            ON UPDATE CASCADE)");
    $db->commit();
  }
}