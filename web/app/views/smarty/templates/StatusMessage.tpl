{extends file="Header.tpl"}
{block name="message"}
    {if !empty($smarty.session.success)}
      {foreach $smarty.session.success as $msg}
        <div class="container-fluid row">
        <div class="col-sm"></div>
        <div class="alert alert-success col-sm-10">{$msg}</div>
        <div class="col-sm"></div>
      {/foreach}
    {elseif !empty($smarty.session.info)}
      {foreach $smarty.session.info as $msg}
        <div class="container-fluid row">
        <div class="col-sm"></div>
        <div class="alert alert-info col-sm-10">{$msg}</div>
        <div class="col-sm"></div>
      {/foreach}
    {elseif !empty($smarty.session.warning)}
      {foreach $smarty.session.warning as $msg}
        <div class="container-fluid row">
        <div class="col-sm"></div>
        <div class="alert alert-warning col-sm-10">{$msg}</div>
        <div class="col-sm"></div>
      {/foreach}
    {elseif !empty($smarty.session.error)}
      {foreach $smarty.session.error as $msg}
        <div class="container-fluid row">
        <div class="col-sm"></div>
        <div class="alert alert-danger col-sm-8">{$msg}</div>
        <div class="col-sm"></div>
        </div>
      {/foreach}
    {/if}
{/block}