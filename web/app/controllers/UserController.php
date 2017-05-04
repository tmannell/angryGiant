<?php

/**
 * Class UserController
 */
Class UserController extends Controller {

  /**
   * @var
   *  Stores HTML_QuickForm Obj.
   */
  protected $form;
  /**
   * @var
   *  Stores submitted form data.
   */
  private $formValues;
  /**
   * @var array|string
   *  User ID
   */
  protected $uid;
  /**
   * @var \User
   *  User Obj.
   */
  private $user;
  /**
   * @var \Validation
   *  Validation Obj.
   */
  protected $validation;

  /**
   * UserController constructor.
   *
   * Inherits constructor from Controller class.
   */
  function __construct() {
    parent::__construct();

    // Set formValues if form has been submitted
    if (!empty($_POST)) {
      $this->formValues = $_POST;
    }

    // If we are loading a form (add, edit, delete)
    // load the validation object.
    if (Helper::explodePath(3) || Helper::explodePath(2) == 'add') {
      $this->validation = new Validation();
    }

    // If we are not adding a new user lets load up the
    // current user obj and store the uid in a separate var.
    if (Helper::explodePath(3) == 'edit' || Helper::explodePath(3) == 'delete' || is_numeric(Helper::explodePath(2))) {

      // Get the story identifier (id or short_title) from URL
      $this->uid = Helper::explodePath(2);

      // Load story obj based on identifier.
      $this->user = new User();
      $this->user->load(['id = ?', $this->uid]);

      // if the obj wasn't populated lets redirect to a 404.
      if (!$this->user->id) {
        $this->f3->error(404);
      }
    }
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
      $this->f3->reroute('/user/' . $this->f3->get('SESSION.uid'));
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
    $this->form->registerRule('check_password', 'function', 'validate_password', $this->validation);
    $this->form->addRule('password', 'Password is incorrect!', 'check_password', 'username');

    // If validation passes then the form has been submitted. Let's process the form values.
    if ($this->form->validate()) {
      // Populate user obj based on username (usernames must be unique)
      $user = new User();
      $user->load(['username = ?', $this->formValues['username']]);
      // Set user id in session var
      $this->f3->set('SESSION.uid', $user->id);
      // and redirect user to their user page.
      $this->f3->reroute('/user/' . $user->id);
    }

    // Create new render obj to render forms
    $renderer = new HTML_QuickForm_Renderer_Tableless();
    // The form must accept the renderer to convert it to html
    $this->form->accept($renderer);

    // Assign vars to template
    $this->assign('form', $renderer->toHtml());
    // And display it.
    $this->display('Form.tpl');
  }

  /**
   * Displays user page.
   *
   * Pulls all user data from the database and
   * passes it to a smarty template.
   */
  function viewUser() {
    $this->assign('username', $this->user->username);
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
    $this->form->registerRule('username_check', 'function', 'validate_username', $this->validation);
    $this->form->addRule('username', 'Username already exists', 'username_check');

    // Custom validation, make sure the passwords match.
    $this->form->registerRule('match_field', 'function', 'validate_match_field', $this->validation);
    $this->form->addRule('password_1', 'Passwords do not match!', 'match_field', $this->formValues['password_2']);

    // Process submitted form.
    if ($this->form->validate()) {
      // Create new user obj with submitted form values.
      $user = new User();
      $user->username = $this->formValues['username'];
      // Encrypt password for security.
      $user->password = $this->cryptPassword($this->formValues['password_1']);
      $user->save();
      // Load the user id and redirect that user page.
      $user->load(["username = ?", $this->formValues['username']]);
      $this->f3->reroute('/user/' . $user->id);
    }
    // If the form hasn't been submitted display the form/template.
    $renderer = new HTML_QuickForm_Renderer_Tableless();
    $this->form->accept($renderer);

    $this->assign('form', $renderer->toHtml());
    $this->display('Form.tpl');
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

    $this->form->registerRule('match_field', 'function', 'validate_match_field', $this->validation);
    $this->form->addRule('password_1', 'Passwords do not match!', 'match_field', $this->formValues['password_2']);

    if ($this->form->validate()) {
      // save encrypted password.
      $this->user->password = $this->cryptPassword($this->formValues['password_1']);
      $this->user->save();

      // Set success message in session var
      Helper::setMessage('Password successfully updated', 'success');
      // Reroute to user view page.
      $this->f3->reroute('/user/' . $this->uid);
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
    // Build form.
    $this->form = new HTML_QuickForm('delete_user', 'POST', $this->f3->get('PATH'));
    $this->form->addElement('submit', 'btnSubmit', 'Delete');
    $this->form->addElement('button','cancel','Cancel','onClick="window.location.href = \'/user\'"');

    // Add custom rule, make sure we aren't deleting user id 1 - super user.
    $this->form->registerRule('check_admin_user', 'function', 'validate_user_deletion', $this->validation);
    $this->form->addRule('current_user', 'Cannot delete admin user.', 'check_admin_user');

    // Process submission.
    if ($this->form->validate()) {
      // Delete user
      $this->user->erase();
      // Success message
      Helper::setMessage('User has been successfully deleted', 'success');
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
}