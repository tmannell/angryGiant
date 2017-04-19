<?php

class Setup {

  protected $f3;
  protected $db;
  protected $form;
  protected $formValues;

  function __construct() {
    $this->f3 = Base::instance();
    $this->db = new \DB\SQL($this->f3->get('sqliteDB'));
  }

  function installCheck() {
    $result = $this->db->exec('SELECT name FROM sqlite_master WHERE type="table" AND name="users"');
    if (empty($result)) {
      $this->installSetupForm();
    } else {
      echo 'The site has already been installed.';
    }
  }

  function installSetupForm() {
    $this->form = new HTML_QuickForm('admin_user_setup', 'POST', '/install');
    $this->form->addElement('text', 'adminUsername', 'Admin Username:');
    $this->form->addElement('password', 'adminPassword_1', 'Admin Password:');
    $this->form->addElement('password', 'adminPassword_2', 'Re-enter Password:');
    $this->form->addElement('submit', 'btnSubmit', 'Submit');

    $this->form->addRule('adminUsername', 'Username is required', 'required');
    $this->form->addRule('adminPassword_1', 'Please enter a password', 'required');
    $this->form->addRule('adminPassword_2', 'Please re-enter your password', 'required');

    $this->form->registerRule('match_field', 'function', 'validate_match_field', $this);
    $this->form->addRule('adminPassword_1', 'Passwords do not match!', 'match_field', 'adminPassword_2');


    if ($this->form->validate()) {
      $this->formValues = $_POST;
      $this->createDatabase();
      $this->insertAdminUser();
      $this->f3->reroute('/');
    }
    else {
      $renderer = new HTML_QuickForm_Renderer_Tableless();
      $this->form->accept($renderer);

      $smarty = new Smarty();
      $smarty->assign('adminForm', $renderer->toHtml());
      $smarty->display($this->f3->get('templates') . 'installSetupForm.tpl');
    }
  }

  function validate_match_field($originalFieldValue, $compareFieldKey) {

    if ($originalFieldValue == $this->form->getElementValue($compareFieldKey)) {
      return true;
    }
    else {
      return false;
    }
  }

  function createDatabase() {
    $db = $this->db;

    $db->begin();
    // Create users table.
    $db->exec(
      "CREATE TABLE IF NOT EXISTS users (
          id       INTEGER PRIMARY KEY AUTOINCREMENT,
          username TEXT NOT NULL UNIQUE,
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

  function insertAdminUser() {
    // Insert Admin info;
    $userManagement = new UserManagement;
    $this->db = new \DB\SQL($this->f3->get('sqliteDB'));
    $result = $this->db->exec(
      "INSERT INTO users (username, password) VALUES (?, ?)",
      [
        1 => $this->formValues['adminUsername'],
        2 => $userManagement->cryptPassword($this->values['adminPassword_1']),
      ]
    );
  }
}

