{extends file="StatusMessage.tpl"}
{block name="content"}

  {if $op == 'delete'}
    {assign var=elementClass value="btn-group inline"}
    
  {else}
    {assign var=elementClass value="form-group"}
  {/if}

  <div class="col-sm-4 offset-sm-4">
    <form {$form.attributes}>
      {foreach $form.elements as $element}
        <div id="{$element.name}" class={$elementClass}>
              {$element.html}
        </div>
      {/foreach}
    </form>
  </div>

  {*<div class="col-sm-4 offset-sm-4">*}
    {*<form {$form.attributes}>*}
      {*{foreach $form.elements as $element}*}
        {*<div id="{$element.name}" class="form-group">*}
          {*<label for="{$element.name}" class="col-form-label">{$element.label}</label>*}
          {*<div>*}
            {*{$element.html}*}
          {*</div>*}
        {*</div>*}
      {*{/foreach}*}
    {*</form>*}
  {*</div>*}

  <script type="text/javascript">
    var errors = {$errors}
    {literal}
      $(document).ready(function() {
          $.each(errors, function(key, value) {
           if (key === 'password_1') {
               $("#password_1, #password_2").addClass(value);
           }
           $("#" + key).addClass(value);
          });
      });
    {/literal}
  </script>
{/block}