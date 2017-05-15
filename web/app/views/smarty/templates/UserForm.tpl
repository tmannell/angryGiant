{extends file="StatusMessage.tpl"}
{block name="content"}
  <div class="col-sm-4 offset-sm-4">
    <form {$formAttr}>
      {foreach $elements as $element}
        {if !preg_match('/^btn/', $element.name)}
          <div id="{$element.name}" class="form-group">
            <label for="{$element.name}" class="col-form-label">{$element.label}</label>
            {if $element.required == 1}<span class="required">*</span>{/if}
            {$element.html}
          </div>
        {/if}
      {/foreach}
      <div id="submit-btns">
        {$elements.btnSubmit.html}
        {$elements.btnCancel.html}
      </div>
    </form>
  </div>

  <script type="text/javascript">
    var errors = {$errors}
    {literal}
      $(document).ready(function() {
          highlightErrors(errors)
      });
    {/literal}
  </script>
{/block}