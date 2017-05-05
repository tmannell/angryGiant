{extends file="StatusMessage.tpl"}
{block name="content"}
  <fieldset>
    <form {$form.attributes}>
      {foreach $form.elements as $element}
        {$element.label}
        {$element.html}
      {/foreach}
    </form>
  </fieldset>

  <script type="text/javascript">
    var errors = {$errors}
    {literal}
    $(document).ready(function() {
        console.log(errors);
    });
    {/literal}
  </script>

{/block}