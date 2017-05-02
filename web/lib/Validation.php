<?php

class Validation extends Controller {

  function __construct() {
    parent::__construct();
  }

  function validatePictureDimensions($file) {
    $image_info = getimagesize($file['tmp_name']);
    $image_width  = $image_info[0];
    $image_height = $image_info[1];
    if ($image_width >= $this->f3->get('imgLarge')
      && $image_height >= $this->f3->get('imgMinHeight')) {
      return true;
    }
    else {
      return false;
    }
  }

  function validateMimeType($file) {
    if ($file['type'] == 'image/jpeg') {
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
  function validate_username($username) {
    $user = new User();
    $user->load(['username = ?', $username]);

    if (isset($user->username) && $user->username) {
      return false;
    }
    else {
      return true;
    }
  }

  /**
   * Validation function
   *  Ensure that two fields that are supposed to match do.
   *
   * @param $originalFieldValue
   *  Value from first password field
   * @param $compareFieldKey
   *  Key for second password field so we can look up value.
   * @return bool
   */
  function validate_match_field($originalFieldValue, $compareFieldKey) {
    if ($originalFieldValue == $compareFieldKey) {
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

  /**
   * Validation function
   *  Validates the password by comparing form submitted data with
   *  user info in the database.
   *
   * @param $password
   *  User submitted password.
   * @param $username
   *  Username form name, used to get actual submitted value.
   * @return bool
   */
  function validate_password($password, $username) {
    $user = new UserController();
    // Pass username and password to our custom auth function.
    if ($user->authenticate_user($username, $password)) {
      return true;
    }
    else {
      return false;
    }
  }

  function validatePageNumber($pageNumber, $storyId) {
    $page = new Page;
    $pages = $page->listPageNumbersInStory($storyId);

    foreach ($pages as $page) {
      if ($page->page_number == $pageNumber) {
        return false;
      }
    }

    return true;
  }
}