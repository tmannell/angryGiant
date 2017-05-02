<?php

Class StoryController extends Controller {

  private $form;
  private $formValues;
  public $sid;

  function __construct() {
    parent::__construct();
    $this->sid = Helper::explodePath(2);
  }

  function viewStory() {
    $story = new Story();
    $story->load(['id = ?', 1 => $this->sid]);
    $this->assign('story', $story);
    $this->display('viewStory.tpl');
  }

  function addStory() {

    $this->storyForm('add');

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
      $story->published = $this->formValues['publish'];
      $story->save();

      $this->f3->reroute('/story/' . $story->get('_id'));
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

  // TODO: add thumbnail to edit page.
  function editStory() {

    $this->storyForm('edit');

    $story = new Story();
    $story->load(['id = ?', $this->sid]);

    $this->form->setDefaults(
      [
        'title'     => $story->title,
        'publish'   => $story->published,
        'date'      => $story->post_date,
      ]
    );

    if ($this->form->validate()) {
      // Put the posted values in a class vars.
      $this->formValues = $_POST;

      $file['path'] = $_FILES['titlePage']['tmp_name'];
      $file['name'] = $_FILES['titlePage']['name'];
      $filename = Helper::resizeAndSaveImage($file);

      $story = new Story();
      $story->load(['id = ?', $this->sid]);
      $story->title = $this->formValues['title'];
      $story->created_by = $this->f3->get('SESSION.uid');
      $story->published = $this->formValues['publish'];
      $story->post_date = (trim($this->formValues['date']) != '') ? $this->formValues['date'] : null;
      $story->save();

      $picture = new Picture();
      $picture->load(['id = ?', $story->picture_id]);
      $picture->filename = $filename;
      $picture->save();
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

  function deleteStory() {
    // Build form.
    $this->form = new HTML_QuickForm('deleteStory', 'POST', $this->f3->get('PATH'));
    $this->form->addElement('submit', 'btnSubmit', 'Delete');
    $this->form->addElement('button','cancel','Cancel','onClick="window.location.href = \'/stories\'"');

    // Process submission.
    if ($this->form->validate()) {
      $this->formValues = $_POST;
      $user = new Story();
      // Delete user
      $user->erase(['id = ?', $this->sid]);
      // Success message
      Helper::setMessage('Story has been successfully deleted', 'success');
      // Reroute to user page.
      $this->f3->reroute('/stories');
    }

    // Display form.
    $renderer = new HTML_QuickForm_Renderer_Tableless();
    $this->form->accept($renderer);

    $this->assign('form', $renderer->toHtml());
    $this->display('Form.tpl');
  }

  function storyAddEditForm($op) {
    if ($op == 'add') {
      $this->form = new HTML_QuickForm('add_story', 'POST', '/story/add');
    }
    else {
      $this->form = new HTML_QuickForm('edit_story', 'POST', $this->f3->get('PATH'));
    }

    $this->form->addElement('text', 'title', 'Title:');

    $this->form->setMaxFileSize(5242880);
    $this->form->addElement('file', 'titlePage', 'Title Page:');

    // todo: wire up javascript date picker and hide date if publish now == yes.
    $this->form->addElement('radio', 'publish', 'Publish now:', 'Yes', true);
    $this->form->addElement('radio', 'publish', null, 'No', false);

    $this->form->addElement('text', 'date', 'Publish Date:', array('id' => 'datepicker'));

    $this->form->addElement('submit', 'btnSubmit', 'Save');

    $this->form->addRule('title', 'Username is required', 'required');
    $this->form->addRule('titlePage', 'Username is required', 'required');

    $this->form->registerRule('pictureDimensions', 'function', 'validatePictureDimensions', $this);
    $ruleMsg = 'Picture Dimensions are too small! Min Width: ' . $this->f3->get('imgLarge') . ' Min Height: ' . $this->f3->get('imgMinHeight');
    $this->form->addRule('titlePage', $ruleMsg, 'pictureDimensions');

    $this->form->registerRule('pictureMimeType', 'function', 'validateMimeType', $this);
    $this->form->addRule('titlePage', 'Picture file type not supported', 'pictureMimeType');
  }

  // TODO: create validation class if possible.
  function validatePictureDimensions($values) {
    $image_info = getimagesize($values['tmp_name']);
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

  function validateMimeType($values) {
    if ($values['type'] == 'image/jpeg') {
      return true;
    }
    else {
      return false;
    }
  }
}