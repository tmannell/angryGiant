<?php
// Set DB instance
\Base::instance()->set('DB', new DB\SQL(\Base::instance()->get('sqliteDB')));

/**
 * Class Story
 */
class Story extends \DB\SQL\Mapper {

  /**
   * Story constructor.
   */
  public function __construct() {
    parent::__construct( \Base::instance()->get('DB'), 'stories');
  }

  /**
   * Lists all stories by title ascending.
   *
   * @return object
   */
  public function listByTitle() {
    return $this->select('id, title', null, array('order' => 'title ASC'));
  }


  public function allStories($published = true, $order) {
    $published = $published ? 'published = 1' : null;
    return $this->select('*', $published, ['order' => $order]);
  }
}