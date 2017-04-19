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
   *
   * Upon successful login adds session var (user id). We are using this var
   * to authorize users.
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
    $this->form->addElement('submit', 'btnSubmit', 'Login');

    // Make username and pw required.
    $this->form->addRule('username', 'Username is required', 'required');
    $this->form->addRule('password', 'Please enter a password', 'required');

    // Add some custom validation, check if password matches the one in the database.
    $this->form->registerRule('check_password', 'function', 'validate_password', $this);
    $this->form->addRule('password', 'Password is incorrect!', 'check_password', 'username');

    // If validation passes then the form has been submitted. Let's process the form values.
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
      // Create new render obj to render forms
      $renderer = new HTML_QuickForm_Renderer_Tableless();
      // The form must accept the renderer to convert it to html
      $this->form->accept($renderer);

      // Assign vars to template
      $this->assign('form', $renderer->toHtml());
      // And display it.
      $this->display('Form.tpl');
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
    $args = explode('/', $this->f3->get('PATH'));
    $user->load(['id = ?', $args[2]]);


    $this->assign('username', $user->username);
    $this->display('View.tpl');
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
    $this->form->addElement('submit', 'btnSubmit', 'Add User');

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

      $this->assign('form', $renderer->toHtml());
      $this->display('Form.tpl');
    }
  }

  /**
   * Edit user form.
   */
  function editUser() {
    // Build form.
    $this->form = new HTML_QuickForm('edit_user', 'POST', $this->f3->get('PATH'));
    $this->form->addElement('password', 'password_1', 'New Password:');
    $this->form->addElement('password', 'password_2', 'Re-enter Password:');
    $this->form->addElement('submit', 'btnSubmit', 'Save');

    // Make password 1 and 2 required.
    $this->form->addRule('password_1', 'Please enter a new password', 'required');
    $this->form->addRule('password_2', 'Please re-enter password', 'required');

    $this->form->registerRule('match_field', 'function', 'validate_match_field', $this);
    $this->form->addRule('password_1', 'Passwords do not match!', 'match_field', 'password_2');

    if ($this->form->validate()) {
      $this->formValues = $_POST;
      $user = new User();
      $url = $this->f3->get('PATH');
      $args = explode('/', $url);
      $user->load(['id = ?', $args[2]]);
      $user->password = $this->cryptPassword($this->formValues['password_1']);
      $user->save();

      // Set success message in session var
      Helper::set_message('Password successfully updated', 'success');
      // Reroute to user view page.
      $this->f3->reroute('/user/' . $args[2] . '/view');
    }

    $renderer = new HTML_QuickForm_Renderer_Tableless();
    $this->form->accept($renderer);

    $this->assign('form', $renderer->toHtml());
    $this->display('Form.tpl');
  }

  /**
   * Deletes user form.
   *   Removes user from database.
   */
  function deleteUser() {
    // Explode url into an array.
    $args = explode('/', $this->f3->get('PATH'));
    // Build form.
    $this->form = new HTML_QuickForm('delete_user', 'POST', $this->f3->get('PATH'));
    $this->form->addElement('hidden', 'current_user', $args[2]);
    $this->form->addElement('submit', 'btnSubmit', 'Delete');
    $this->form->addElement('button','cancel','Cancel','onClick="window.location.href = \'/user\'"');

    // Add custom rule, make sure we aren't deleting user id 1 - super user.
    $this->form->registerRule('check_admin_user', 'function', 'validate_user_deletion', $this);
    $this->form->addRule('current_user', 'Cannot delete admin user.', 'check_admin_user');

    // Process submission.
    if ($this->form->validate()) {
      $this->formValues = $_POST;
      $user = new User();
      // Delete user
      $user->erase(['id = ?', $args[2]]);
      // Success message
      Helper::set_message('User has been successfully deleted', 'success');
      // Reroute to user page.
      $this->f3->reroute('/user');
    }

    // Display form.
    $renderer = new HTML_QuickForm_Renderer_Tableless();
    $this->form->accept($renderer);

    $this->assign('form', $renderer->toHtml());
    $this->display('Form.tpl');
  }

  /**
   * Logs user out by clearing uid in session var.
   */
  function logout() {
    $this->f3->clear('SESSION.uid');
    $this->f3->reroute('/');
  }

  /**
   * Uses PHP crypt to encrypt password using BlowFish
   *
   * @param $input
   *  Input from password submission form.
   * @param int $cost
   *  Rounds of encryption
   * @return string
   *  returns the password hash for storage in database.
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
   * Authenticates user passwords, compares them to the hash in the database.
   *
   * @param $username
   *  Username retrieved from form
   * @param $password
   *  Password retrieved from form.
   * @return bool
   *  returns password hash if correct otherwise returns false.
   *
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
   * Validation function
   *  Makes sure username does not already exist.
   *
   * @param $value
   *
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
   * Validation function
   *  Makes sure super user is not being deleted.
   *
   * @param $uid
   *  User id of user being deleted
   * @return bool
   */
  function validate_user_deletion($uid) {
    if ($uid == 1) {
      return false;
    }
    else {
      return true;
    }
  }
}