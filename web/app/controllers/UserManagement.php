<?php

/**
 * Class UserManagement
 */
Class UserManagement extends Controller {

  /**
   * @var
   *  Stores submitted form data.
   */
  private $formValues;
  /**
   * @var
   *  Stores HTML_QuickForm Obj.
   */
  private $form;

  /**
   * UserManagement constructor.
   *
   * Inherits constructor from Controller class.
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Admin login form.
   */
  function login() {

    // Reroute user to view user page is they are already logged in.
    $authStatus = $this->getAuthorizationStatus();
    if ($authStatus == 'authorized' || $authStatus == 'admin' ) {
      $this->f3->reroute('/user/' . $this->f3->get('SESSION.uid') . '/view');
    }

    // Build Login form
    $this->form = new HTML_QuickForm('user_login', 'POST', '/user');
    $this->form->addElement('text', 'username', 'Username:');
    $this->form->addElement('password', 'password', 'Password:');
    $this->form->addElement('submit', 'btnSubmit', 'Submit');

    // Make username and pw required.
    $this->form->addRule('username', 'Username is required', 'required');
    $this->form->addRule('password', 'Please enter a password', 'required');

    // Add some custom validation, check if password matches the one in the database.
    $this->form->registerRule('check_password', 'function', 'validate_password', $this);
    $this->form->addRule('password', 'Password is incorrect!', 'check_password', 'username');

    // If this passes then the form has been submitted and everything
    // has passed validation. Let's process the form.
    if ($this->form->validate()) {
      // Put the posted values in a class var.
      $this->formValues = $_POST;
      // Populate user obj based on username (usernames must be unique)
      $user = new User();
      $user->load(['username = ?', $this->formValues['username']]);
      // Set user id in session var
      $this->f3->set('SESSION.uid', $user->id);
      // and redirect user to their user page.
      $this->f3->reroute('/user/' . $user->id . '/view');
    }
    else {
      // Call renderer class to render forms
      $renderer = new HTML_QuickForm_Renderer_Tableless();
      // The form must accept the renderer to convert it to html
      $this->form->accept($renderer);

      // Create a new smarty template
      $smarty = new Smarty();
      // Assign vars to template
      $smarty->assign('loginForm', $renderer->toHtml());
      // And display it.
      $smarty->display($this->f3->get('templates') . 'loginForm.tpl');
    }
  }

  /**
   * Validation function
   *  Validates the password by comparing form submitted data with
   *  user info in the database.
   *
   * @param $password
   *  User submitted password.
   * @param $usernameFieldKey
   *  Username form name, used to get actual submitted value.
   * @return bool
   */
  function validate_password($password, $usernameFieldKey) {
     // Get the submitted username
     $username = $this->form->getElementValue($usernameFieldKey);
     // Pass username and password to our custom auth function.
     if ($this->authenticate_user($username, $password)) {
       return true;
     }
     else {
       return false;
     }
  }

  /**
   * Displays user page.
   *
   * Pulls all user data from the database and
   * passes it to a smarty template.
   */
  function viewUser() {
    $user = new User;
    $args = explode('/', this->f3->get('PATH'));
    $user->load(['id = ?', $args[2]]);

    $smarty = new Smarty();
    $smarty->assign('username', $user->username);
    $smarty->display($this->f3->get('templates') . 'viewUser.tpl');
  }

  /**
   * Create new user form.
   */
  function addUser() {
    // Build form.
    $this->form = new HTML_QuickForm('add_user', 'POST', '/user/add');
    $this->form->addElement('text', 'username', 'Username:');
    $this->form->addElement('password', 'password_1', 'Password:');
    $this->form->addElement('password', 'password_2', 'Re-enter Password:');
    $this->form->addElement('submit', 'btnSubmit', 'Submit');

    // Add validation - all fields below are required.
    $this->form->addRule('username', 'Username is required', 'required');
    $this->form->addRule('password_1', 'Please enter a password', 'required');
    $this->form->addRule('password_2', 'Please re-enter your password', 'required');

    // Custom validation, make sure username doesn't already exist.
    $this->form->registerRule('username_check', 'function', 'validate_username', $this);
    $this->form->addRule('username', 'Username already exists', 'username_check');

    // Custom validation, make sure the passwords match.
    $this->form->registerRule('match_field', 'function', 'validate_match_field', $this);
    $this->form->addRule('password_1', 'Passwords do not match!', 'match_field', 'password_2');

    // Process submitted form.
    if ($this->form->validate()) {
      $this->formValues = $_POST;
      // Create new user obj with submitted form values.
      $user = new User();
      $user->username = $this->formValues['username'];
      // Encrypt password for security.
      $user->password = $this->cryptPassword($this->formValues['password_1']);
      $user->save();
      // Load the user id and redirect that user page.
      $user->load(["username = ?", $this->formValues['username']]);
      $this->f3->reroute('/user/' . $user->id . '/view');
    }
    // If the form hasn't been submitted display the form/template.
    else {
      $renderer = new HTML_QuickForm_Renderer_Tableless();
      $this->form->accept($renderer);

      $smarty = new Smarty();
      $smarty->assign('addUserForm', $renderer->toHtml());
      $smarty->display($this->f3->get('templates') . 'addUserForm.tpl');
    }
  }

  /**
   * @param $value
   * @return bool
   */
  function validate_username($value) {
    $user = new User();
    $user->load(['username =?', $value]);

    if ($user->username) {
      return false;
    }
    else {
      return true;
    }
  }

  /**
   * @param $originalFieldValue
   * @param $compareFieldKey
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
   *
   */
  function editUser() {
    $this->form = new HTML_QuickForm('edit_user', 'POST', $this->f3->get('PATH'));
    $this->form->addElement('password', 'password_1', 'New Password:');
    $this->form->addElement('password', 'password_2', 'Re-enter Password:');
    $this->form->addElement('submit', 'btnSubmit', 'Submit');

    $this->form->addRule('password_1', 'Please enter a new password', 'required');
    $this->form->addRule('password_2', 'Please re-enter password', 'required');

    // todo: clear passwords fields after save

    $this->form->registerRule('match_field', 'function', 'validate_match_field', $this);
    $this->form->addRule('password_1', 'Passwords do not match!', 'match_field', 'password_2');


    $smarty = new Smarty();

    if ($this->form->validate()) {
      $this->formValues = $_POST;
      $user = new User();
      $url = $this->f3->get('PATH');
      $args = explode('/', $url);
      $user->load(['id = ?', $args[2]]);
      $user->password = $this->cryptPassword($this->formValues['password_1']);
      $user->save();

      $smarty->assign('successMsg', true);
    }

    $renderer = new HTML_QuickForm_Renderer_Tableless();
    $this->form->accept($renderer);

    $smarty->assign('editUserForm', $renderer->toHtml());
    $smarty->display($this->f3->get('templates') . 'editUserForm.tpl');
  }

  /**
   *
   */
  function deleteUser() {

    $args = explode('/', $this->f3->get('PATH'));
    $this->form = new HTML_QuickForm('delete_user', 'POST', $this->f3->get('PATH'));
    $this->form->addElement('hidden', 'current_user', $args[2]);
    $this->form->addElement('submit', 'btnSubmit', 'Submit');
    $this->form->addElement('button','Cancel','Cancel Deletion','onClick="window.location.href = \'/user\'"');

    $this->form->registerRule('check_admin_user', 'function', 'validate_user_deletion', $this);
    $this->form->addRule('current_user', 'Cannot delete admin user.', 'check_admin_user');

    if ($this->form->validate()) {
      $this->formValues = $_POST;
      $user = new User();
      $user->erase(['id = ?', $args[2]]);
      $this->f3->reroute('/');
    }

    $smarty = new Smarty;
    $renderer = new HTML_QuickForm_Renderer_Tableless();
    $this->form->accept($renderer);

    $smarty->assign('deleteUserForm', $renderer->toHtml());
    $smarty->display($this->f3->get('templates') . 'deleteUserForm.tpl');
  }

  /**
   * @param $value
   * @return bool
   */
  function validate_user_deletion($value) {
    if ($value == 1) {
      return false;
    }
    else {
      return true;
    }
  }

  /**
   *
   */
  function logout() {
    $this->f3->clear('SESSION.uid');
    $this->f3->reroute('/');
  }

  /**
   * @param $input
   * @param int $cost
   * @return string
   */
  function cryptPassword($input, $cost = 7) {
    $salt = "";
    $salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
    for($i=0; $i < 22; $i++) {
      $salt .= $salt_chars[array_rand($salt_chars)];
    }
    return crypt($input, sprintf('$2a$%02d$', $cost) . $salt);
  }

  /**
   * @param $username
   * @param $password
   * @return bool
   */
  function authenticate_user($username, $password) {
    $db = $this->db;
    $result = $db->exec('SELECT * FROM users WHERE username = ?', [1 => $username]);
    if(crypt($password, $result[0]['password']) == $result[0]['password']) {
      return $result[0]['password'];
    } else {
      return false;
    }
  }
}