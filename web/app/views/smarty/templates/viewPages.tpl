{extends file="StatusMessage.tpl"}
{block name="content"}
      <div class="row center-block">
        <div class="col-md-8 col-sm-offset-2">
          {foreach $pages as $page}
              <div class="col-md-8 col-md-offset-2 gallery">
                <div class="img-cluster">
                  <a class="story-thumb" href="/"><img class="img-responsive" src="/pictures/thumbnail/" /></a>
                  <div class="story-thumb-overlay centre-block">
                    <div class="overlay-title"></div>
                    <div class="overlay-nav">
                      <a class="ibrowse" title="Browse Pages" href="//pages"><i class="fa fa-list" aria-hidden="true"></i></a>
                      <a class="istart" title="First Page" href="//1"><i class="fa fa-play" aria-hidden="true"></i></a>
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