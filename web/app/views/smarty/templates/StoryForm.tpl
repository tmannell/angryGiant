{extends file="StatusMessage.tpl"}
{block name="content"}
    <form {$formAttr}>
      <div class="row">
        {if $op == 'add' || $op == 'edit'}
          <div class="col-sm-4 offset-sm-2">
            <div id="title" class="form-group">
              <label>{$elements.title.label}</label>
              <span class="required">*</span>
              {$elements.title.html}
            </div>
            <div id="short-title" class="form-group">
              <label>{$elements.shortTitle.label}</label>
              <span class="required">*</span>
              {$elements.shortTitle.html}
            </div>
            <div id="title-page" class="form-group">
              <label>{$elements.titlePage.label}</label>
              <span class="required">*</span>
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
            <div id="date-container">
              <div id="date" class="form-group" style="display:none;">
                <label>{$elements.date.label}</label>
                {$elements.date.html}
              </div>
            </div>
          </div>
        {/if}
        {if $op == 'edit' || $op == 'delete'}
          <div class="col-sm-4 {if $op == 'delete'}offset-sm-2{/if}">
            <div id="story-thumb"><img class=".thumbnail" src="/pictures/thumbnail/{$filename}" alt="$title" /></div>
          </div>
        {/if}
      </div>
      <div class="row">
        <div class="col-sm-4 offset-sm-2">
          <div id="submit-btns">
            {$elements.btnSubmit.html}
            {$elements.btnCancel.html}
          </div>
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

          var picker = new Pikaday({
              field: $('#datepicker')[0],
              format: 'MMM-DD-YYYY',
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