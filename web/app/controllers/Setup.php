<?php

/**
 * Class Setup
 */
class Setup extends Controller {

  /**
   * @var
   *  Base Instance inherited from controller.
   */
  protected $f3;
  /**
   * @var
   *  DB connection inheritied from controller.
   */
  protected $db;
  /**
   * @var
   *  Form stored in class, used to access values in validation.
   */
  protected $form;
  /**
   * @var
   *  Submitted form values.
   */
  protected $formValues;

  /**
   * Setup constructor.
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Checks install.
   *
   * Before anything is done this function is called
   * to check if the databse has been installed already.
   */
  function installCheck() {
    // Check if user table exists for check.
    $result = $this->db->exec('SELECT name FROM sqlite_master WHERE type="table" AND name="users"');
    if (empty($result)) {
      $this->installSetupForm();
    } else {
      echo 'The site has already been installed.';
    }
  }

  /**
   * Setup Form
   *  Asks user for admin username and password before installing the database.
   */
  function installSetupForm() {
    // Build form.
    $this->form = new HTML_QuickForm('admin_user_setup', 'POST', '/install');
    $this->form->addElement('text', 'adminUsername', 'Admin Username:');
    $this->form->addElement('password', 'adminPassword_1', 'Admin Password:');
    $this->form->addElement('password', 'adminPassword_2', 'Re-enter Password:');
    $this->form->addElement('submit', 'btnSubmit', 'Install');

    // Add validation
    $this->form->addRule('adminUsername', 'Username is required', 'required');
    $this->form->addRule('adminPassword_1', 'Please enter a password', 'required');
    $this->form->addRule('adminPassword_2', 'Please re-enter your password', 'required');

    // Custom validation.  Checks if passwords match.
    $this->form->registerRule('match_field', 'function', 'validate_match_field', $this);
    $this->form->addRule('adminPassword_1', 'Passwords do not match!', 'match_field', 'adminPassword_2');

    // If form passes validation install site.
    if ($this->form->validate()) {
      $this->formValues = $_POST;
      // First create the database.
      $this->createDatabase();
      // Then insert the admin user.
      $this->insertAdminUser();
      // Send user back to home page.
      $this->f3->reroute('/');
    }
    else {
      // Render the form into html.
      $renderer = new HTML_QuickForm_Renderer_Tableless();
      $this->form->accept($renderer);
      // And output to template.
      $this->assign('form', $renderer->toHtml());
      $this->display('Form.tpl');
    }
  }

  /**
   * Validation function
   *  When editing or creating a password makes sure both passwords entered
   *  match.
   *
   * @param $originalFieldValue
   *  Value from first password field
   * @param $compareFieldKey
   *  Key for second password field so we can look up value.
   * @return bool
   */
  function validate_match_field($originalFieldValue, $compareFieldKey) {

    if ($originalFieldValue == $this->form->getElementValue($compareFieldKey)) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Creates the database according to the
   * AngryGiant schema.
   */
  function createDatabase() {
    $db = $this->db;

    $db->begin();
    // Create users table.
    // Username is unique and case insensitive.
    $db->exec(
      "CREATE TABLE IF NOT EXISTS users (
          id       INTEGER PRIMARY KEY AUTOINCREMENT,
          username TEXT NOT NULL UNIQUE COLLATE NOCASE,
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

  /**
   *  Adds the admin user and encrypted password to the database
   */
  function insertAdminUser() {
    // Insert Admin info;
    $userController = new UserController;
    $this->db = new \DB\SQL($this->f3->get('sqliteDB'));
    $this->db->exec(
      "INSERT INTO users (username, password) VALUES (?, ?)",
      [
        1 => $this->formValues['adminUsername'],
        2 => $userController->cryptPassword($this->formValues['adminPassword_1']),
      ]
    );
  }
}

