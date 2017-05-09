{extends file="StatusMessage.tpl"}
{block name="content"}

  {if $op == 'delete'}
    {assign var=elementClass value="btn-group inline"}
    {assign var=formTitle value="Are you sure you want to delete <em>$object</em>"}
  {elseif $op == 'add'}
    {assign var=elementClass value="form-group"}
    {assign var=formTitle value="Add <em>$object</em>"}
  {elseif $op == 'edit'}
    {assign var=elementClass value="form-group"}
    {assign var=formTitle value="Edit <em>$object</em>"}
  {/if}

  <div id="form-title" class="col-sm-4 offset-sm-4"><h3>{$formTitle}</h3></div>


    <form {$attributes}>
      <div class="row">
        <div class="col-sm-4 offset-sm-2">
          <div id="title" class="form-group">
            <label>{$elements.title.label}</label>
            {$elements.title.html}
          </div>
          <div id="title-page" class="form-group">
            <label>{$elements.titlePage.label}</label>
            {$elements.titlePage.html}
          </div>
          <div id="publish" class="form-group">
            {foreach $elements.publish as $radio}
              {if $radio.label != ''}
                <legend class="col-form-legend">{$radio.label}</legend>
              {/if}
              <div class="publish form-check form-check-inline form-check-label">{$radio.html}</div>
            {/foreach}
          </div>
          <div id="date" class="form-group" style="display:none;">
            <label>{$elements.date.label}</label>
            {$elements.date.html}
          </div>
        </div>
        <div class="col-sm-2">
          {if $op == 'edit'}
            <div class="text-center" id="title-age-image"><img src="{$imgPath}" alt="$title" /></div>
          {/if}
          <div class="text-center">{$elements.btnSubmit.html}</div>
        </div>
      </div>
    </form>


  <script type="text/javascript">
    var errors = {$errors}
    {literal}
      $(document).ready(function() {
          highlightErrors()

          $(".publish input:radio").click(function() {

              if ($(this).val() !== '1') {
                      $('#date').fadeIn('slow')
              }
              else if($(this).val() === '1') {
                  $('#date').fadeOut('slow');
              }

          });
      });

      function highlightErrors() {
          $.each(errors, function(key, value) {
              if (key === 'password_1') {
                  $("#password_1, #password_2").addClass(value);
              }
              else {
                  $("#" + key).addClass(value);
              }
          });
      }
    {/literal}
  </script>
{/block}