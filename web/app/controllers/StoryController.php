<?php

Class StoryController extends Controller {

  private $form;
  private $formValues;
  public $sid;

  function __construct() {
    $this->sid = Helper::explodePath(2);
  }

  function viewStory() {
    $story = new Story();
    $story->load(['id = ?', 1 => $this->sid]);
    $this->assign('story', $story);
    $this->display('viewStory.tpl');
  }

  function addStory() {
    $this->form = new HTML_QuickForm('add_story', 'POST', '/story/add');
    $this->form->addElement('text', 'title', 'Title:');

    $this->form->setMaxFileSize(5242880);
    $this->form->addElement('file', 'titlePage', 'Title Page:');

    $this->form->addElement('text', 'date', 'Publish Date:', array('id' => 'datepicker'));
    $this->form->addElement('submit', 'btnSubmit', 'Save');

    $this->form->addRule('title', 'Username is required', 'required');
    $this->form->addRule('story_cover_picture', 'Username is required', 'required');

    if ($this->form->validate()) {
      // Put the posted values in a class vars.
      $this->formValues = $_POST;

      $file['path'] = $_FILES['titlePage']['tmp_name'];
      $file['name'] = $_FILES['titlePage']['name'];
      $filename = Helper::resizeAndSaveImage($file);

      $picture = new Picture();
      $picture->filename = $filename;
      $picture->save();

      $story = new Story();
      $story->title = $this->formValues['title'];
      $story->picture_id = $picture->get('_id');
      $story->created_by = $this->f3->get('SESSION.uid');
      $story->post_date = (trim($this->formValues['date']) != '') ? $this->formValues['date'] : null;
      $story->save();
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

  function editStory() {

  }

  function deleteStory() {

  }
}