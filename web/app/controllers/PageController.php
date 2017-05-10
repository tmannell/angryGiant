<?php

/**
 * Class PageController
 */
class PageController extends Controller {

  /**
   * @var
   *  The form object
   */
  protected $form;
  /**
   * @var
   *  Values from form after submit.
   */
  private $formValues;
  /**
   * @var \Page
   *  Page Obj loaded from short_title or story id and page number in the URL.
   */
  private $page;
  /**
   * @var string
   *  The number of the page in the story
   *  take from URL.
   */
  protected $pageNumber;
  /**
   * @var string
   *  The identifier (short_title|id) of the story loaded
   *  from the URL.
   */
  protected $identifier;
  /**
   * @var \Story
   *  Story Obj loaded from identifier in the URL.
   */
  private $story;
  /**
   * @var \Validation
   *  Our validation Obj.
   */
  protected $validation;

  /**
   * PageController constructor.
   */
  public function __construct() {
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

    // If we are not adding a new page lets load up the
    // current page obj and current story obj and store
    // the page number and story identifier in a separate var.
    if (Helper::explodePath(2) != 'add') {
      $this->pageNumber = Helper::explodePath(2);
      $this->identifier = Helper::explodePath(1);

      $this->story = new Story();
      $this->story->load(['id = ? or short_title = ?', $this->identifier, $this->identifier]);

      $this->page = new Page();
      $this->page->load(['story_id = ? and page_number = ?', $this->story->id, $this->pageNumber]);

      // if the page obj wasn't populated lets redirect to a 404.
      if (!$this->page->id) {
        $this->f3->error(404);
      }
    }
  }

  /**
   * View page callback.
   */
  public function viewPage() {
    // Make sure the story and the page are set to publish before displaying.
    if ($this->story->published == 1 && $this->page->published == 1) {
      $this->assign('page', $this->page);
      $this->display('viewPage.tpl');
    }
    else {
      // Otherwise send to 404.
      $this->f3->error(404);
    }
  }

  function viewPages() {
    $page = new Page();
    $pages = $page->allPages($this->story->id, 'post_date desc');
    $this->assign('story', $pages);
    $this->display('viewPages.tpl');
  }

  /**
   * Add Page Form.
   */
  public function addPage() {
    // Build form via the pageForm function.
    $this->pageForm('add');
    // Set defaults, make sure publish is default to true.
    $this->form->setDefaults(['publish' => true]);

    // If form is submitted and all validation passes
    // save the page.
    if ($this->form->validate()) {

      // Prep file for resize and save.
      $file['path'] = $_FILES['pagePicture']['tmp_name'];
      $file['name'] = $_FILES['pagePicture']['name'];
      // Resize and save image file.
      $filename = Helper::resizeAndSaveImage($file);

      // Save new picture obj in db.
      $picture = new Picture();
      $picture->filename = $filename;
      $picture->save();

      // Save new page obj in db.
      $page = new Page();
      $page->picture_id  = $picture->get('_id');
      $page->description = $this->formValues['description'];
      $page->story_id    = $this->formValues['story'];
      $page->page_number = $this->formValues['pageNumber'];
      $page->created_by  = $this->f3->get('SESSION.uid');
      $page->post_date   = (trim($this->formValues['date']) != '') ? $this->formValues['date'] : null;
      $page->published   = $this->formValues['publish'];
      $page->save();

      Helper::setMessage('Page has been successfully added', 'success');

      // Lets load up the short title so we can put it in the reroute url.
      $story = new Story();
      $story->load(['id = ?', $this->formValues['story']]);
      // Upon save reroute to the new page.
      $this->f3->reroute( '/' . $this->short_title . '/' . $this->formValues['pageNumber']);
    }
    // If the form hasn't been submitted render the form.
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
   * Edit page form.
   */
  public function editPage() {
    // Build edit form.
    $this->pageForm('edit');
    // Set form defaults based on the current page.
    $this->form->setDefaults([
      'description' => $this->page->description,
      'story'       => $this->page->story_id,
      'pageNumber'  => $this->page->page_number,
      'date'        => $this->page->post_date,
      'publish'     => $this->page->published,
    ]);

    // If form has been submitted update the page in db.
    if ($this->form->validate()) {
      // If a new picture has been submitted save, resize, update db.
      if (isset($_FILES['name'])) {
        // Prep file for resize and save.
        $file['path'] = $_FILES['pagePicture']['tmp_name'];
        $file['name'] = $_FILES['pagePicture']['name'];

        // Resize and save file.
        $filename = Helper::resizeAndSaveImage($file);

        // Update the filename of the picture.
        $picture = new Picture();
        $picture->load(['id = ?', $this->page->picture_id]);
        $picture->filename = $filename;
        $picture->save();
      }

      // Update the page info.
      $this->page->description = $this->formValues['description'];
      $this->page->story_id    = $this->formValues['story'];
      $this->page->page_number = $this->formValues['pageNumber'];
      $this->page->created_by  = $this->f3->get('SESSION.uid');
      $this->page->post_date   = (trim($this->formValues['date']) != '') ? $this->formValues['date'] : null;
      $this->page->published   = $this->formValues['publish'];
      // Save the page.
      $this->page->save();

      Helper::setMessage('Page has been successfully updated', 'success');

      // Upon save reroute to the view of current page.
      $this->f3->reroute( '/' . $this->formValues['story'] . '/' . $this->formValues['pageNumber']);
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
   *  Delete page form.
   */
  public function deletePage() {
    // Build form.
    $this->form = new HTML_QuickForm('deletePage', 'POST', $this->f3->get('PATH'));
    $this->form->addElement('submit', 'btnSubmit', 'Delete');
    $this->form->addElement('button','cancel','Cancel','onClick="window.location.href = \'/' . $this->story->short_title . '/' . $this->pageNumber . '\'"');

    // Process submission.
    if ($this->form->validate()) {
      // Delete page
      $this->page->erase();
      // Success message
      Helper::setMessage('Page has been successfully deleted', 'success');
      // Reroute to user page.
      $this->f3->reroute('/' . $this->story->short_title);
    }

    // Display form.
    $renderer = new HTML_QuickForm_Renderer_Tableless();
    $this->form->accept($renderer);

    $this->assign('form', $renderer->toHtml());
    $this->display('Form.tpl');
  }

  /**
   * pageForm builds the add and edit page forms.
   *
   * @param $op
   *   Operation of the form (add|edit)
   */
  protected function pageForm($op) {
    // Generate new form object based on operation.
    if ($op == 'add') {
      $this->form = new HTML_QuickForm('add_page', 'POST', '/page/add');
    }
    else {
      $this->form = new HTML_QuickForm('edit_page', 'POST', $this->f3->get('PATH'));
    }

    // Set max file size for upload.
    $this->form->setMaxFileSize(5242880);
    // Add form elements.
    $this->form->addElement('file', 'pagePicture', 'Picture:');
    $this->form->addElement('textarea', 'description', 'Text:');

    // Load all story titles for select box.
    $stories = new Story();
    foreach ($stories->listByTitle() as $story) {
      $options[$story->id] = $story->title;
    }

    $this->form->addElement('select', 'story', 'Select Story:', $options);
    $this->form->addElement('text', 'pageNumber', 'Page Number:');

    // todo: wire up javascript date picker and hide date if publish now == yes.
    $this->form->addElement('radio', 'publish', 'Publish now:', 'Yes', true);
    $this->form->addElement('radio', 'publish', null, 'No', false);

    $this->form->addElement('text', 'date', 'Publish Date:', array('id' => 'datepicker'));

    $this->form->addElement('submit', 'btnSubmit', 'Save');

    // Add validation to forms.
    // Only require a file upload on the add form.
    if ($op == 'add') {
      $this->form->addRule('pagePicture', 'File is required', 'uploadedfile');
    }

    $this->form->addRule('pageNumber', 'Page Number is Required', 'required');

    // Custom validation rules found in \Validation
    // Page number must be unique to this story.
    $this->form->registerRule('pageNumberUnique', 'function', 'validatePageNumber', $this->validation);
    $this->form->addRule('pageNumber', 'Page number already exists.', 'pageNumberUnique', $this->formValues['story']);

    // Picture dimensions must be of specific size before upload.
    $this->form->registerRule('pictureDimensions', 'function', 'validatePictureDimensions', $this->validation);
    $ruleMsg = 'Picture Dimensions are too small! Min Width: ' . $this->f3->get('imgLarge') . ' Min Height: ' . $this->f3->get('imgMinHeight');
    $this->form->addRule('pagePicture', $ruleMsg, 'pictureDimensions');

    // Picture Mime Type must be of type jpg.
    $this->form->registerRule('pictureMimeType', 'function', 'validateMimeType', $this->validation);
    $this->form->addRule('pagePicture', 'Picture file type not supported', 'pictureMimeType');
  }
}