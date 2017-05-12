{extends file="Navigation.tpl"}
{block name="header"}
  {if isset($contentTitle)}
    <div id="header"><span id="title">{$contentTitle}</span> <span id="obj-type">{$object}</span></div>
  {/if}
{/block}