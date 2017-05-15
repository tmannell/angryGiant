<?php

/**
 * Class Setup
 */
class Setup extends Controller {

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
   * @var \Validation
   *  Validation obj.
   */
  protected $validation;

  /**
   * Setup constructor.
   */
  function __construct() {
    parent::__construct();

    // Set formValues if form has been submitted
    if (!empty($_POST)) {
      $this->formValues = $_POST;
    }

    $this->validation = new Validation();
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
    $this->form->addElement('text', 'adminUsername', 'Admin Username', ['class' => 'form-control']);
    $this->form->addElement('password', 'adminPassword_1', 'Admin Password', ['class' => 'form-control']);
    $this->form->addElement('password', 'adminPassword_2', 'Re-enter Password', ['class' => 'form-control']);
    $this->form->addElement('submit', 'btnSubmit', 'Install', ['class' => 'btn btn-primary']);

    // Add validation
    $this->form->addRule('adminUsername', 'Username is required', 'required');
    $this->form->addRule('adminPassword_1', 'Please enter a password', 'required');
    $this->form->addRule('adminPassword_2', 'Please re-enter your password', 'required');

    // Custom validation.  Checks if passwords match.
    $this->form->registerRule('match_field', 'function', 'validate_match_field', $this->validation);
    $this->form->addRule('adminPassword_1', 'Passwords do not match!', 'match_field', $this->formValues['adminPassword_2']);

    // If form passes validation install site.
    if ($this->form->validate()) {
      // First create the database.
      $this->createDatabase();
      // Then insert the admin user.
      $this->insertAdminUser();
      // Send user back to home page.
      $this->f3->reroute('/');
    }
    $renderer = new HTML_QuickForm_Renderer_ArraySmarty($this->smarty);
    $this->form->accept($renderer);

    $errors = Helper::checkErrors($renderer);

    // Add all form elements to the template.
    if (!empty($errors)) {
      $this->assign('errors', json_encode($errors));
    }
    $rendered = Helper::modifyRenderedOutput($renderer->toArray());

    $this->assign('elements', $rendered['elements']);
    $this->assign('formAttr', $rendered['attributes']);
    $this->assign('op', 'install');
    $this->assign('contentTitle', 'Install AngryGiant');

    $this->display('InstallForm.tpl');
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
          id       INTEGER PRIMARY KEY AUTOINCREMENT,
          filename TEXT NOT NULL UNIQUE)");
    $db->exec("CREATE INDEX picture_filename_idx ON pictures (FILENAME)");
    // Create stories table.
    $db->exec(
      "CREATE TABLE IF NOT EXISTS stories (
          id          INTEGER PRIMARY KEY AUTOINCREMENT,
          title       TEXT NOT NULL,
          authors     TEXT NULL COLLATE NOCASE,
          short_title TEXT NOT NULL UNIQUE COLLATE NOCASE,
          picture_id  INTEGER NOT NULL,
          post_date   TEXT,
          created_by  INTEGER NOT NULL,
          published   INTEGER NOT NULL,
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
          page_number INTEGER NULL, 
          post_date   TEXT,
          created_by  INTEGER NOT NULL,
          published   INTEGER NOT NULL,
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

    $db->exec(
      "CREATE VIEW fullStory AS
        SELECT pictures.filename,
               stories.id as sid, stories.title, stories.short_title, stories.post_date as story_post_date, stories.created_by as story_uid, stories.published as story_published,
               pages.id as pgid, pages.description, pages.page_number, pages.post_date as page_post_date, pages.created_by as page_uid, pages.published as page_published
        FROM stories
        INNER JOIN pictures on stories.picture_id = pictures.id
        LEFT JOIN pages on stories.id = pages.story_id"
    );
  }

  /**
   *  Adds the admin user and encrypted password to the database
   */
  function insertAdminUser() {
    // Insert Admin info;
    $userController = new UserController;
    $this->db->exec(
      "INSERT INTO users (username, password) VALUES (?, ?)",
      [
        1 => $this->formValues['adminUsername'],
        2 => $userController->cryptPassword($this->formValues['adminPassword_1']),
      ]
    );
  }
}

