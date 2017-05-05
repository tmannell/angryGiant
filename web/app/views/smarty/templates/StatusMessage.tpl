{extends file="Navigation.tpl"}
{block name="message"}
    {if isset($smarty.session.success)}
      <message>
        <div class="success">{$smarty.session.success}</div>
      </message>
    {elseif isset($smarty.session.warning)}
      <message>
        <div class="warning">{$smarty.session.warning}</div>
      </message>
    {elseif isset($smarty.session.error)}
      <message>
      <div class="alert alert-danger">{$smarty.session.error}</div>
      </message>
    {/if}
{/block}