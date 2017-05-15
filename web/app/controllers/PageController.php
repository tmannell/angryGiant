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
    if (Helper::explodePath(2) != 'add' && Helper::explodePath(1) != 'fetch') {
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

      Helper::setMessage('Page has been successfully added!', 'success');

      // Lets load up the short title so we can put it in the reroute url.
      $story = new Story();
      $story->load(['id = ?', $this->formValues['story']]);
      // Upon save reroute to the new page.
      $this->f3->reroute( '/' . $this->short_title . '/' . $this->formValues['pageNumber']);
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
    $this->assign('pageTitle', 'Add page');
    $this->assign('op', 'add');
    $this->assign('object', 'page');
    $this->assign('contentTitle', 'Add');
    $this->display('PageForm.tpl');
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

      Helper::setMessage('Page has been successfully updated!', 'success');

      // Upon save reroute to the view of current page.
      $this->f3->reroute( '/' . $this->formValues['story'] . '/' . $this->formValues['pageNumber']);
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

    // Lets load the full story for some extra info.
    $this->fullStory->load(['sid = ?', $this->page->story_id]);

    // Finally lets get this rendered array and modify it slightly
    // so it's easier to use the vars in the template.
    $rendered = Helper::modifyRenderedOutput($renderer->toArray());

    // Assign all the vars to the template.
    $this->assign('elements', $rendered['elements']);
    $this->assign('formAttr', $rendered['attributes']);
    $this->assign('pageTitle', 'Edit page');
    $this->assign('op', 'edit');
    $this->assign('object', $this->fullStory->title . ': page #' . $this->page->page_number);
    $this->assign('contentTitle', 'Edit');
    $this->assign('filename', $this->fullStory->filename);
    $this->display('PageForm.tpl');
  }

  /**
   *  Delete page form.
   */
  public function deletePage() {
    // Build form.


    // Process submission.
    if ($this->form->validate()) {
      // Delete page
      $this->page->erase();
      // Success message
      Helper::setMessage('Page has been successfully deleted!', 'success');
      // Reroute to user page.
      $this->f3->reroute('/' . $this->story->short_title);
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

    // Lets load the full story for some extra info.
    $this->fullStory->load(['sid = ?', $this->page->story_id]);

    // Finally lets get this rendered array and modify it slightly
    // so it's easier to use the vars in the template.
    $rendered = Helper::modifyRenderedOutput($renderer->toArray());

    // Assign all the vars to the template.
    $this->assign('elements', $rendered['elements']);
    $this->assign('formAttr', $rendered['attributes']);
    $this->assign('pageTitle', 'Edit page');
    $this->assign('op', 'edit');
    $this->assign('object', $this->fullStory->title . ': page #' . $this->page->page_number);
    $this->assign('contentTitle', 'Delete');
    $this->assign('filename', $this->fullStory->filename);
    $this->display('PageForm.tpl');
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
      $btnLabel = 'Add';
    }
    elseif ($op == 'edit') {
      $this->form = new HTML_QuickForm('edit_page', 'POST', $this->f3->get('PATH'));
      $btnLabel = 'Save';
    }
    elseif ($op == 'delete') {
      $this->form = new HTML_QuickForm('deletePage', 'POST', $this->f3->get('PATH'));
      $btnLabel = 'Delete';
    }

    // These fields only apply to add edit operations.
    if ($op == 'add' || $op == 'edit') {
      // Set max file size for upload.
      $this->form->setMaxFileSize($this->f3->get('maxFileSize'));
      // Add form elements.
      $this->form->addElement('file', 'pagePicture', 'Picture', ['class' => 'form-control']);
      $this->form->addElement('textarea', 'description', 'Text', ['class' => 'form-control']);

      // Load all story titles for select box.
      $stories = new Story();
      $options[0] = 'Select Story';
      foreach ($stories->listByTitle() as $story) {
        $options[$story->id] = $story->title;
      }

      $this->form->addElement('select', 'story', 'Select story', $options, ['class' => 'custom-select']);
      $this->form->addElement('select', 'pageNumber', 'Page Number', ['You must choose a story first'], ['id' => 'page-number-select', 'class' => 'custom-select']);
      $this->form->addElement('radio', 'publish', 'Publish', 'Now', true, ['class' => 'form-check-input', 'id' => 'publish1']);
      $this->form->addElement('radio', 'publish', null, 'Later', false, ['class' => 'form-check-input', 'id' => 'publish1']);
      $this->form->addElement('text', 'date', 'Publish Date', ['class' => 'form-control', 'id' => 'datepicker']);
    }

    $this->form->addElement('submit', 'btnSubmit', $btnLabel, ['class' => 'btn btn-primary']);
    $this->form->addElement('button','btnCancel','Cancel',['onClick' => "window.history.back();", 'class' => 'btn btn-outline-primary']);

    // Add validation to forms.
    if ($op == 'add' || $op == 'edit') {

      // Only require a file upload on the add form.
      if ($op == 'add') {
        $this->form->addRule('pagePicture', 'File is required', 'uploadedfile');
      }

      $this->form->addRule('story', 'You must select a story', 'required');
      $this->form->addRule('story', 'You must select a story', 'nonzero');
      // Page number is required.
      $this->form->addRule('pageNumber', 'Page number is required', 'required');
      $this->form->addRule('pageNumber', 'Page number is required', 'nonzero');

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

  function getPageNumbers() {
    if (isset($_POST['sid'])) {
      $sid = $_POST['sid'];
      $page = new Page();
      $pages = $page->allPages($sid, 'page_number ASC');
      $i = 0;
      foreach ($pages as $page) {
        $i++;
        if ($page->page_number == $i) {
          continue;
        }
        $options[$i] = $i;
      }
      $options[$i + 1] = $i + 1;
      echo '[' . implode(',', $options) . ']';
    }
    else {
      $this->f3->error(404);
    }
  }
}