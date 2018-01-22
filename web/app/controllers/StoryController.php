<?php

/**
 * Class StoryController
 */
Class StoryController extends Controller {

  /**
   * @var
   *  The form object
   */
  protected $form;
  /**
   * @var
   *  Values from form after submit
   */
  private $formValues;
  /**
   * @var array|string
   *  Story unique identifier.
   */
  public $identifier;
  /**
   * @var
   *  The story obj.
   */
  private $story;
  /**
   * @var \Validation
   *  Validation obj.
   */
  private $validation;

  /**
   * StoryController constructor.
   */
  function __construct() {
    parent::__construct();

    // Set formValues if form has been submitted
    if (!empty($_POST)) {
      $this->formValues = $_POST;
    }

    // If we are loading a add, edit, delete form
    // load the validation object.
    $op = Helper::explodePath(2);
    if ($op = 'add' || $op == 'edit' || $op == 'delete') {
      $this->validation = new Validation();
    }

    // If we are not adding a new story lets load up the
    // current story obj and store the story identifier in a separate var.
    if (Helper::explodePath(2) != 'add' && Helper::explodePath(1) != 'stories') {
      // Get the story identifier (id or short_title) from URL
      $this->identifier = Helper::explodePath(1);

      // Load story obj based on identifier.
      $this->story = new Story();
      $this->story->load(['id = ? or short_title = ?', $this->identifier, $this->identifier]);
      // if the obj wasn't populated lets redirect to a 404.
      if (!$this->story->id) {
        $this->f3->error(404);
      }
    }
  }

  /**
   * View story callback
   *  Displays the title page of the story!
   */
  function viewStory() {
    // Make sure the story is set to publish before displaying.
    if ($this->story->published == 1) {

      $picture = new Picture();
      $picture->load(['id = ?', $this->story->picture_id]);
      $page = $this->db->exec('SELECT MIN(page_number) as firstPage FROM pages WHERE story_id = ?', $this->story->id);

      $this->assign('story', $this->story);
      $this->assign('role', $this->getAuthorizationStatus());
      $this->assign('contentTitle', $this->story->title);
      $this->assign('pageTitle', $this->story->title);
      $this->assign('firstPage', $page[0]['firstPage']);
      $this->assign('filename', $picture->filename);
      $this->display('viewStory.tpl');
    }
    else {
      // Otherwise send to 404.
      $this->f3->error(404);
    }
  }

  function viewStories() {
    $story = new Story();
    $stories = $story->allStories(true, 'post_date DESC');
    $this->assign('role', $this->getAuthorizationStatus());
    $this->assign('contentTitle', 'Stories');
    $this->assign('pageTitle', 'All Stories');
    $this->assign('stories', $stories);
    $this->display('viewStories.tpl');
  }

  /**
   *  Add story form.
   */
  function addStory() {
    // Build add story form.
    $this->storyForm('add');
    // Set publish default value to yes.
    $this->form->setDefaults(['publish' => true]);

    // If form is submitted and all validation passes
    // save the story.
    if ($this->form->validate()) {

      // Prep file for resize and save.
      $file['path'] = $_FILES['titlePage']['tmp_name'];
      $file['name'] = $_FILES['titlePage']['name'];
      // Resize and save image file.
      $filename = Helper::resizeAndSaveImage($file);
      // Save new picture obj in db.
      $picture = new Picture();
      $picture->filename = $filename;
      $picture->save();

      // Save new story obj in db.
      $story = new Story();
      $story->title       = $this->formValues['title'];
      $story->short_title = $this->formValues['shortTitle'];
      $story->authors     = trim($this->formValues['authors']);
      $story->picture_id  = $picture->get('_id');
      $story->created_by  = $this->f3->get('SESSION.uid');
      $story->post_date   = (trim($this->formValues['date']) != '') ? $this->formValues['date'] : null;
      $story->published   = $this->formValues['publish'];
      $story->save();

      Helper::setMessage('Story has been successfully added', 'success');

      // Upon save reroute to new story.
      $this->f3->reroute('/' . $this->formValues['shortTitle']);
    }

    // New renderer, one that renders the form as a smarty array
    $renderer = new HTML_QuickForm_Renderer_ArraySmarty($this->smarty);
    // Pass the form through the renderer.
    $this->form->accept($renderer);

    // Check the renderer array for errors
    $errors = Helper::checkErrors($renderer);
    // If there are errors pass them to the template in json format.
    if (!empty($errors)) {
      $this->assign('errors', json_encode($errors));
    }
    // Finally lets get this rendered array and modify it slightly
    // so it's easier to use the vars in the template.
    $rendered = Helper::modifyRenderedOutput($renderer->toArray());

    // Assign all the vars to the template.
    $this->assign('elements', $rendered['elements']);
    $this->assign('formAttr', $rendered['attributes']);
    $this->assign('pageTitle', 'Add Story');
    $this->assign('op', 'add');
    $this->assign('object', 'story');
    $this->assign('contentTitle', 'Add');
    $this->display('StoryForm.tpl');
  }

  /**
   *  Edit story form.
   */
  function editStory() {
    // Build story form.
    $this->storyForm('edit');
    // Set form defaults based on current story.
    $this->form->setDefaults(
      [
        'title'      => $this->story->title,
        'shortTitle' => $this->story->short_title,
        'authors'    => $this->story->authors,
        'publish'    => $this->story->published,
        'date'       => $this->story->post_date,
      ]
    );

    // If form has been submitted update the page in db.
    if ($this->form->validate()) {

      // If a new picture has been submitted save, resize, update db.
      if (!empty($_FILES['titlePage']['name'])) {
        // Prep file for resize and save.
        $file['path'] = $_FILES['titlePage']['tmp_name'];
        $file['name'] = $_FILES['titlePage']['name'];
        // Resize and save file.
        $filename = Helper::resizeAndSaveImage($file);

        $picture = new Picture();
        $picture->load(['id = ?', $this->story->picture_id]);
        $picture->filename = $filename;
        $picture->save();
      }

      $this->story->title       = trim($this->formValues['title']);
      $this->story->short_title = trim($this->formValues['shortTitle']);
      $this->story->authors     = trim($this->formValues['authors']);
      $this->story->created_by  = $this->f3->get('SESSION.uid');
      $this->story->post_date   = ($this->formValues['publish'] == true) ? trim($this->formValues['date']) : null;
      $this->story->published   = $this->formValues['publish'];
      $this->story->save();

      Helper::setMessage('Story has been successfully saved', 'success');

      // Upon save reroute to new story.
      $this->f3->reroute('/' . $this->formValues['shortTitle']);
    }

    // New renderer, one that renders the form as a smarty array
    $renderer = new HTML_QuickForm_Renderer_ArraySmarty($this->smarty);
    // Pass the form through the renderer.
    $this->form->accept($renderer);

    // Check the renderer array for errors
    $errors = Helper::checkErrors($renderer);
    // If there are errors pass them to the template in json format.
    if (!empty($errors)) {
      $this->assign('errors', json_encode($errors));
    }
    // Finally lets get this rendered array and modify it slightly
    // so it's easier to use the vars in the template.
    $rendered = Helper::modifyRenderedOutput($renderer->toArray());

    // Let's load the picture to get the filename.
    $picture = new Picture();
    $picture->load(['id = ?', $this->story->picture_id]);

    // Assign all the vars to the template.
    $this->assign('elements', $rendered['elements']);
    $this->assign('formAttr', $rendered['attributes']);
    $this->assign('pageTitle', 'Edit Story: ' . $this->story->title);
    $this->assign('op', 'edit');
    $this->assign('object', $this->story->title);
    $this->assign('contentTitle', 'Edit');
    $this->assign('filename', $picture->filename);
    $this->display('StoryForm.tpl');
  }

  /**
   *  Delete story form.
   */
  function deleteStory() {
    // Build form.
    $this->storyForm('delete');

    // Process submission.
    if ($this->form->validate()) {
      // Delete story
      $this->story->erase();
      // Success message
      Helper::setMessage('Story has been successfully deleted', 'success');
      // Reroute to user page.
      $this->f3->reroute('/stories');
    }
    // If the form hasn't been submitted render the form.
    // Create new render obj to render forms
    $renderer = new HTML_QuickForm_Renderer_ArraySmarty($this->smarty);
    $this->form->accept($renderer);

    $errors = Helper::checkErrors($renderer);

    // Add all form elements to the template.
    if (!empty($errors)) {
      $this->assign('errors', json_encode($errors));
    }

    // New renderer, one that renders the form as a smarty array
    $renderer = new HTML_QuickForm_Renderer_ArraySmarty($this->smarty);
    // Pass the form through the renderer.
    $this->form->accept($renderer);

    // Check the renderer array for errors
    $errors = Helper::checkErrors($renderer);
    // If there are errors pass them to the template in json format.
    if (!empty($errors)) {
      $this->assign('errors', json_encode($errors));
    }
    // Finally lets get this rendered array and modify it slightly
    // so it's easier to use the vars in the template.
    $rendered = Helper::modifyRenderedOutput($renderer->toArray());

    // Let's load the picture to get the filename.
    $picture = new Picture();
    $picture->load(['id = ?', $this->story->picture_id]);

    // Assign all the vars to the template.
    $this->assign('elements', $rendered['elements']);
    $this->assign('formAttr', $rendered['attributes']);
    $this->assign('pageTitle', 'Delete Story: ' . $this->story->title);
    $this->assign('op', 'delete');
    $this->assign('object', $this->story->title);
    $this->assign('contentTitle', 'Delete');
    $this->assign('filename', $picture->filename);
    $this->display('StoryForm.tpl');
  }

  /**
   * storyForm builds the add and edit story forms.
   *
   * @param $op
   *   Operation of the form (add|edit)
   */
  function storyForm($op) {
    // Generate new form object based on operation.
    if ($op == 'add') {
      $this->form = new HTML_QuickForm('add_story', 'POST', '/story/add');
      $btnLabel = 'Add';
    }
    elseif ($op == 'edit') {
      $this->form = new HTML_QuickForm('edit_story', 'POST', $this->f3->get('PATH'));
      $btnLabel = 'Save';
    }
    elseif ($op == 'delete') {
      $this->form = new HTML_QuickForm('deleteStory', 'POST', $this->f3->get('PATH'));
      $btnLabel = 'Delete';
    }

    // These fields only apply to add and edit operations.
    if ($op == 'add' || $op == 'edit') {
      // Add form elements
      $this->form->addElement('text', 'title', 'Title', ['class' => 'form-control']);
      $this->form->addElement('text', 'authors', 'Author(s)', ['class' => 'form-control']);
      $this->form->addElement('text', 'shortTitle', 'URL Friendly Title', ['class' => 'form-control']);

      // Set max size for file upload
      $this->form->setMaxFileSize($this->f3->get('maxFileSize'));
      $this->form->addElement('file', 'titlePage', 'Title Page', ['class' => 'form-control']);
      $this->form->addElement('radio', 'publish', 'Publish', 'Now', true, ['class' => 'form-check-input', 'id' => 'publish1']);
      $this->form->addElement('radio', 'publish', null, 'Later', false, ['class' => 'form-check-input', 'id'    => 'publish2']);
      $this->form->addElement('text', 'date', 'Publish Date', ['class' => 'form-control', 'id' => 'datepicker']);
    }

    // Display buttons for all operations.
    $this->form->addElement('submit', 'btnSubmit', $btnLabel, ['class' => 'btn btn-primary']);
    $this->form->addElement('button','btnCancel','Cancel',['onClick' => "window.location.href='/stories'", 'class' => 'btn btn-outline-primary']);

    // Rules only apply for add and edit operations.
    if ($op == 'add' || $op == 'edit') {
      // Add validation.
      $this->form->addRule('title', 'Title is required', 'required');
      $this->form->addRule('shortTitle', 'Require field', 'required');

      // Only require a file upload on the add form.
      if ($op == 'add') {
        $this->form->addRule('titlePage', 'File is required', 'uploadedfile');
      }

      // Add custom validation rules found in \Validation.
      $this->form->registerRule('uniqueShortTitle', 'function', 'validateShortTitle', $this->validation);
      $this->form->addRule('shortTitle', 'Short Title already exists', 'uniqueShortTitle', $this->identifier);
      // Picture dimensions must be specific size before upload
      $this->form->registerRule('pictureDimensions', 'function', 'validatePictureDimensions', $this->validation);
      $ruleMsg = 'Picture Dimensions are too small! Min Width: ' . $this->f3->get('imgLarge') . ' Min Height: ' . $this->f3->get('imgMinHeight');
      $this->form->addRule('titlePage', $ruleMsg, 'pictureDimensions');

      // Picture Mime Type must be of type jpg.
      $this->form->registerRule('pictureMimeType', 'function', 'validateMimeType', $this->validation);
      $this->form->addRule('titlePage', 'Picture file type not supported', 'pictureMimeType');

    }
  }
}