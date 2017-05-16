{extends file="StatusMessage.tpl"}
{block name="content"}
<div class="container">
  <div class="row">
    <div class="col-sm-12 text-center">
      {if isset($firstPage)}
        <a href="/{$story.short_title}/{$firstPage}">
          <img id="title-page" class="img-fluid" src="/pictures/medium/{$filename}" />
        </a>
      {else}
        <img id="title-page" class="img-fluid" src="/pictures/medium/{$filename}" />
      {/if}
    </div>
  </div>
  <div class="row">
    <div class="col-sm-12 text-center"?>
      {if isset($firstPage)}
        <a class="first-page" href="/{$story.short_title}/{$firstPage}">Begin</a>
      {else}
        <div class="first-page">Coming Soon!</div>
      {/if}
    </div>
  </div>
</div>
{/block}