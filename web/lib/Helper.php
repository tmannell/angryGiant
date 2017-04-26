<?php

/**
 * Class Helper
 */
Class Helper extends Controller {

  /**
   * Adds a message to the session var for display on all pages.
   *
   * @param      $message
   *  Text of the message
   * @param      $type
   *  Type of message, three types but more could be added success, error, warning.
   *  The type determines which css class to use
   * @param bool $repeat
   *  If repeat is set to true message will not be cleared until a new
   *  msg is added.
   */
  static function setMessage($message, $type, $repeat = false) {
    $f3 = \Base::instance();
    $f3->set("SESSION.$type", $message);
    $f3->set("SESSION.repeat", $repeat);
  }

  static function explodePath($key = null) {
    $f3 = \Base::instance();
    if (is_null($key)) {
      $args = explode('/', $f3->get('PATH'));
    } else {
      $args = explode('/', $f3->get('PATH'))[$key];
    }

    return $args;
  }

  static function resizeAndSaveImage($file) {
    $f3 = \Base::instance();
    while (true) {
      $patterns = array('/\.[^.jpg]/', '/\s/');
      $replacements = array('', '_');
      $filename = preg_replace($patterns, $replacements, uniqid('', true) . $file['name']);

      if (!file_exists($f3->get('webroot') . 'pictures/original/' . $filename)) {
        break;
      }
    }

    $picture = new \Eventviva\ImageResize($file['path']);
    $picture->save($f3->get('webroot') . 'pictures/original/' . $filename);
    $picture->resizeToWidth($f3->get('imgLarge'));
    $picture->save($f3->get('webroot') . 'pictures/large/' . $filename);
    $picture->resizeToWidth($f3->get('imgMedium'));
    $picture->save($f3->get('webroot') . 'pictures/medium/' . $filename);
    $picture->resizeToWidth($f3->get('imgThumbnail'));
    $picture->save($f3->get('webroot') . 'pictures/thumbnail/' . $filename);

    return $filename;
  }
}