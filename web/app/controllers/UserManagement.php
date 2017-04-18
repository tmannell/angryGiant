<?php

Class UserManagement extends Controller {

  private $formValues;
  private $form;

  function __construct() {
    parent::__construct();
  }

  function login() {

    // Reroute user to view user page is they are already logged in.
    $authStatus = $this->getAuthorizationStatus();
    if ($authStatus == 'authorized' || $authStatus == 'admin' ) {
      $this->f3->reroute('/user/' . $this->f3->get('SESSION.uid') . '/view');
    }

    // Build form
    $this->form = new HTML_QuickForm('user_login', 'POST', '/user');
    $this->form->addElement('text', 'username', 'Username:');
    $this->form->addElement('password', 'password', 'Password:');
    $this->form->addElement('submit', 'btnSubmit', 'Submit');

    $this->form->addRule('username', 'Username is required', 'required');
    $this->form->addRule('password', 'Please enter a password', 'required');

    $this->form->registerRule('check_password', 'function', 'validate_password', $this);
    $this->form->addRule('password', 'Password is incorrect!', 'check_password', 'username');

    // If the validate function returns true, the form has been submitted and
    // passed validation.
    if ($this->form->validate()) {
      $this->formValues = $_POST;
      $user = new User();
      $auth = new \Auth($user, ['id' => 'username', 'pw' => 'password']);
      $passed = $auth->login(
        $this->formValues['username'],
        $this->validate_user($this->formValues['username'], $this->formValues['password'])
      );
      if ($passed == true) {
        $this->f3->set('SESSION.uid', $user->id);
        $this->f3->reroute('/user/' . $user->id . '/view');
      }
    }
    else {
      $renderer = new HTML_QuickForm_Renderer_Tableless();
      $this->form->accept($renderer);

      $smarty = new Smarty();
      $smarty->assign('loginForm', $renderer->toHtml());
      $smarty->display($this->f3->get('templates') . 'loginForm.tpl');
    }
  }

  function validate_password($password, $usernameFieldKey) {
     $username = $this->form->getElementValue($usernameFieldKey);
     if ($this->validate_user($username, $password)) {
       return true;
     }
     else {
       return false;
     }
  }

  function viewUser() {
    $user = new User;
    $url = $this->f3->get('PATH');
    $args = explode('/', $url);
    $user->load(['id = ?', $args[2]]);

    $smarty = new Smarty();
    $smarty->assign('username', $user->username);
    $smarty->display($this->f3->get('templates') . 'viewUser.tpl');
  }

  function addUser() {
    $this->form = new HTML_QuickForm('add_user', 'POST', '/user/add');
    $this->form->addElement('text', 'username', 'Username:');
    $this->form->addElement('password', 'password_1', 'Password:');
    $this->form->addElement('password', 'password_2', 'Re-enter Password:');
    $this->form->addElement('submit', 'btnSubmit', 'Submit');

    $this->form->addRule('username', 'Username is required', 'required');
    $this->form->addRule('password_1', 'Please enter a password', 'required');
    $this->form->addRule('password_2', 'Please re-enter your password', 'required');

    $this->form->registerRule('username_check', 'function', 'validate_username', $this);
    $this->form->addRule('username', 'Username already exists', 'username_check');

    $this->form->registerRule('match_field', 'function', 'validate_match_field', $this);
    $this->form->addRule('password_1', 'Passwords do not match!', 'match_field', 'password_2');


    if ($this->form->validate()) {
      $this->formValues = $_POST;

      $result = $this->db->exec(
        "INSERT INTO users (username, password) VALUES (?, ?)",
        [
          1 => $this->formValues['username'],
          2 => $this->cryptPassword($this->formValues['password_1']),
        ]
      );

      if ($result) {
        $user = new User();
        $user->load(["username=?", $this->formValues['username']]);
        $this->f3->reroute('/user/' . $user->id . '/view');
      }
      else {
        // TODO: display error message
      }
    }
    else {
      $renderer = new HTML_QuickForm_Renderer_Tableless();
      $this->form->accept($renderer);

      $smarty = new Smarty();
      $smarty->assign('addUserForm', $renderer->toHtml());
      $smarty->display($this->f3->get('templates') . 'addUserForm.tpl');
    }
  }

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

  function validate_match_field($originalFieldValue, $compareFieldKey) {

    if ($originalFieldValue == $this->form->getElementValue($compareFieldKey)) {
      return true;
    }
    else {
      return false;
    }
  }

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

  function deleteUser() {
    // todo: add delete user functionality only for admin and can't delete admin.
  }

  function logout() {
    $this->f3->clear('SESSION.uid');
    $this->f3->reroute('/');
  }

  function cryptPassword($input, $cost = 7) {
    $salt = "";
    $salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
    for($i=0; $i < 22; $i++) {
      $salt .= $salt_chars[array_rand($salt_chars)];
    }
    return crypt($input, sprintf('$2a$%02d$', $cost) . $salt);
  }

  function validate_user($username, $password) {
    $db = $this->db;
    $result = $db->exec('SELECT * FROM users WHERE username = ?', [1 => $username]);
    if(crypt($password, $result[0]['password']) == $result[0]['password']) {
      return $result[0]['password'];
    } else {
      return false;
    }
  }
}