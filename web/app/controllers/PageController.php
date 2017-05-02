<?php

class PageController extends Controller {

  private $pageWeight;
  private $shortTitle;
  private $form;
  private $validation;
  private $formValues;

  function __construct() {
    parent::__construct();
    $this->validation = new Validation();
    $this->pageWeight = Helper::explodePath(2);
    $this->shortTitle = Helper::explodePath(1);
  }

  function  viewPage() {

  }

  function addPage() {
    $this->pageForm('add');
    $this->form->setDefaults(['publish' => true]);

    if ($this->form->validate()) {
      // Put the posted values in a class vars.
      $this->formValues = $_POST;


      $file['path'] = $_FILES['pagePicture']['tmp_name'];
      $file['name'] = $_FILES['pagePicture']['name'];
      $filename = Helper::resizeAndSaveImage($file);

      $picture = new Picture();
      $picture->filename = $filename;
      $picture->save();

      $page = new Page();
      $page->picture_id = $picture->get('_id');
      $page->description = $this->formValues['description'];
      $page->story_id   = $this->formValues['story'];
      $page->page_number = $this->formValues['pageNumber'];
      $page->created_by = $this->f3->get('SESSION.uid');
      $page->post_date = (trim($this->formValues['date']) != '') ? $this->formValues['date'] : null;
      $page->published = $this->formValues['publish'];
      $page->save();

      $this->f3->reroute( '/' . $this->formValues['story'] . '/' . $this->formValues['pageNumber']);
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

  function editPage() {

  }

  function pageForm($op) {
    if ($op == 'add') {
      $this->form = new HTML_QuickForm('add_page', 'POST', '/page/add');
    }
    else {
      $this->form = new HTML_QuickForm('edit_page', 'POST', $this->f3->get('PATH'));
    }

    $this->form->setMaxFileSize(5242880);
    $this->form->addElement('file', 'pagePicture', 'Picture:');
    $this->form->addElement('textarea', 'description', 'Text:');

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

    $this->form->addRule('pagePicture', 'File is required', 'uploadedfile');
    $this->form->addRule('pageNumber', 'Page Number is Required', 'required');

    $this->form->registerRule('pageNumberUnique', 'function', 'validatePageNumber', $this->validation);
    $this->form->addRule('pageNumber', 'Page number already exists.', 'pageNumberUnique', $_POST['story']);

    $this->form->registerRule('pictureDimensions', 'function', 'validatePictureDimensions', $this->validation);
    $ruleMsg = 'Picture Dimensions are too small! Min Width: ' . $this->f3->get('imgLarge') . ' Min Height: ' . $this->f3->get('imgMinHeight');
    $this->form->addRule('pagePicture', $ruleMsg, 'pictureDimensions');

    $this->form->registerRule('pictureMimeType', 'function', 'validateMimeType', $this->validation);
    $this->form->addRule('pagePicture', 'Picture file type not supported', 'pictureMimeType');
  }

  function deletePage() {

  }
}