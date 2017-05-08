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

    // If we are loading a form (add, edit, delete)
    // load the validation object.
    if (Helper::explodePath(3) || Helper::explodePath(2) == 'add') {
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
      $this->assign('story', $this->story);
      $this->display('viewStory.tpl');
    }
    else {
      // Otherwise send to 404.
      $this->f3->error(404);
    }
  }

  function viewStories() {
    $story = new Story();
    $stories = $story->allStories(true, 'post_date desc');
    $this->assign('story', $stories);
    $this->display('viewStories.tpl');
  }

  function viewStoryTOC() {
    $page = new Page();
    $pages = $page->allPages($this->story->id, 'post_date desc');
    $this->assign('story', $pages);
    $this->display('storyTOC.tpl');
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
      $story->title = $this->formValues['title'];
      $story->short_title = $this->formValues['shortTitle'];
      $story->picture_id = $picture->get('_id');
      $story->created_by = $this->f3->get('SESSION.uid');
      $story->post_date = (trim($this->formValues['date']) != '') ? $this->formValues['date'] : null;
      $story->published = $this->formValues['publish'];
      $story->save();

      Helper::setMessage('Story has been successfully added', 'success');

      // Upon save reroute to new story.
      $this->f3->reroute('/' . $story->get('_id'));
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
    $this->assign('op', 'add');
    $this->assign('object', 'story');

    $this->assign('form', $renderer->toArray());

    $this->display('Form.tpl');
  }

  /**
   *  Edit story form.
   */
  function editStory() {
    // TODO: add thumbnail to edit page.

    // Build story form.
    $this->storyForm('edit');
    // Set form defaults based on current story.
    $this->form->setDefaults(
      [
        'title'     => $this->story->title,
        'publish'   => $this->story->published,
        'date'      => $this->story->post_date,
      ]
    );

    // If form has been submitted update the page in db.
    if ($this->form->validate()) {
      // If a new picture has been submitted save, resize, update db.
      if (isset($_FILES['name'])) {
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

      $this->story->title       = $this->formValues['title'];
      $this->story->short_title = $this->formValues['shortTitle'];
      $this->story->created_by  = $this->f3->get('SESSION.uid');
      $this->story->published   = $this->formValues['publish'];
      $this->story->post_date   = (trim($this->formValues['date']) != '') ? $this->formValues['date'] : null;
      $this->story->save();
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
   *  Delete story form.
   */
  function deleteStory() {
    // Build form.
    $this->form = new HTML_QuickForm('deleteStory', 'POST', $this->f3->get('PATH'));
    $this->form->addElement('submit', 'btnSubmit', 'Delete');
    $this->form->addElement('button','cancel','Cancel','onClick="window.location.href = \'/stories\'"');

    // Process submission.
    if ($this->form->validate()) {
      // Delete story
      $this->story->erase();
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
    }
    else {
      $this->form = new HTML_QuickForm('edit_story', 'POST', $this->f3->get('PATH'));
    }

    // Add form elements
    $this->form->addElement('text', 'title', 'Title', ['class' => 'form-control']);
    $this->form->addElement('text', 'shortTitle', 'URL Friendly Title', ['class' => 'form-control']);

    // Set max silze for file upload
    $this->form->setMaxFileSize(5242880);
    $this->form->addElement('file', 'titlePage', 'Title Page', ['class' => 'form-control']);

    // todo: wire up javascript date picker and hide date if publish now == yes.
    $this->form->addElement('radio', 'publish', 'Publish', 'Now', true, ['class' => 'form-check-input', 'id' => 'publish1']);
    $this->form->addElement('radio', 'publish', null, 'Later', false, ['class' => 'form-check-input', 'id' => 'publish2']);

    $this->form->addElement('text', 'date', 'Publish Date', ['class' => 'form-control', 'id' => 'datepicker']);

    $this->form->addElement('submit', 'btnSubmit', 'Save', ['class' => 'btn btn-outline-primary']);

    // Add validation.
    $this->form->addRule('title', 'Title is required', 'required');
    $this->form->addRule('shortTitle', 'Require field', 'required');
    // Only require a file upload on the add form.
    if ($op == 'add') {
      $this->form->addRule('titlePage', 'File is required', 'uploadedfile');
    }

    // Add custom validation rules found in \Validation.
    // Picture dimensions must be specific size before upload
    $this->form->registerRule('pictureDimensions', 'function', 'validatePictureDimensions', $this->validation);
    $ruleMsg = 'Picture Dimensions are too small! Min Width: ' . $this->f3->get('imgLarge') . ' Min Height: ' . $this->f3->get('imgMinHeight');
    $this->form->addRule('titlePage', $ruleMsg, 'pictureDimensions');

    // Picture Mime Type must be of type jpg.
    $this->form->registerRule('pictureMimeType', 'function', 'validateMimeType', $this->validation);
    $this->form->addRule('titlePage', 'Picture file type not supported', 'pictureMimeType');
  }
}