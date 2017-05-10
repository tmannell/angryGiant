{extends file="StatusMessage.tpl"}
{block name="content"}
  <div id="form-title" >{$formTitle}</div>
  <div class="col-sm-4 offset-sm-4">
    <form {$formAttr}>
      {foreach $elements as $element}
          <div id="{$element.name}" class="form-group">
            <label for="{$element.name}" class="col-form-label">{$element.label}</label>
            {$element.html}
          </div>
      {/foreach}
    </form>
  </div>

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