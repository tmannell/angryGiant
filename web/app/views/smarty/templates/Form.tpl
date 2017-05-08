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
  <div class="col-sm-4 offset-sm-4">
    <form {$form.attributes}>
      {foreach $form.elements as $element}
        {if $element.type == 'radio'}

            {if $element.label != ''}
              <legend class="col-form-legend">{$element.label}</legend>
            {/if}
            <div class="publish form-check form-check-inline form-check-label">{$element.html}</div>

        {elseif $element.name == 'date'}
          <div id="{$element.name}" class="{$elementClass}" style="display:none;">
            {if $element.type != 'hidden'}
              <label for="{$element.name}" class="col-form-label">{$element.label}</label>
            {/if}
            {$element.html}
          </div>
        {else}
          <div id="{$element.name}" class={$elementClass}>
            {if $element.type != 'hidden'}
              <label for="{$element.name}" class="col-form-label">{$element.label}</label>
            {/if}
            {$element.html}
          </div>
        {/if}

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