{extends file="StatusMessage.tpl"}
{block name="content"}
  <div id="gallery" class="container">
    <div class="row">
        {foreach $stories as $story}
          <div class="col-sm-4 text-center">
            <div class="img-link">
            <a href="/{$story.short_title}">
              <div class="story-title">{$story.title}</div>
              <img class="thumbnail" src="/pictures/thumbnail/{$story.filename}"/>
            </a>
            {if $role != 'anonymous'}
              <div class="action-icons">
                <a class="btn btn-info" href="/{$story.short_title}/pages" aria-label="View">
                  <i class="fa fa-th fa-lg" aria-hidden="true"></i>
                </a>
                <a class="btn btn-info" href="/{$story.short_title}/edit" aria-label="Edit">
                  <i class="fa fa-pencil-square-o fa-lg" aria-hidden="true"></i>
                </a>
                {if $role == 'admin'}
                <a class="btn btn-danger" href="/{$story.short_title}/delete" aria-label="Delete">
                  <i class="fa fa-trash-o fa-lg" aria-hidden="true"></i>
                </a>
                {/if}
              </div>
            {/if}
            </div>
          </div>
        {/foreach}
    </div>
  </div>
{/block}