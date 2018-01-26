{extends file="StatusMessage.tpl"}
{block name="content"}
      <div class="row center-block">
        <div class="col-sm-8 col-sm-offset-2">
              {foreach $stories as $story}
                  <div class="col-sm-6">
                    <div class="img-cluster">
                    <a class="story-thumb" href="/{$story.short_title}"><img class="img-responsive center-block" src="/pictures/thumbnail/{$story.filename}" /></a>
                    <div class="story-thumb-overlay centre-block">
                      <div class="overlay-title">{$story.title}</div>
                      <div class="overlay-nav">
                        <a class="ibrowse" title="Browse Pages" href="/{$story.short_title}/pages"><i class="fa fa-list" aria-hidden="true"></i></a>
                        <a class="istart" title="First Page" href="/{$story.short_title}/1"><i class="fa fa-play" aria-hidden="true"></i></a>
                      </div>
                    </div>
                    </div>
                  </div>
              {/foreach}
        </div>
      </div>
    </div>
  <!-- /#page-content-wrapper -->

  </div>
  <!-- /#wrapper -->
{/block}