{extends file="Layout.tpl"}
{include file="StatusMessage.tpl"}

{block name="content"}
  {$username}
  <a href="/logout">logout</a>
{/block}
