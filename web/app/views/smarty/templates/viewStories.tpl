{extends file="StatusMessage.tpl"}
{block name="content"}
      <div class="row center-block">
        <div class="col-sm-6 col-sm-offset-3">
          <table class="table">
            <thead>
              <th></th>
              <th>Title</th>
              <th>Author</th>
              <th>Posted</th>
              <th>Status</th>
            </thead>
            <tbody>
              {foreach $stories as $story}

                  <tr>
                    <td><a href="/{$story.short_title}"><img class="img-responsive" src="/pictures/thumbnail/{$story.filename}" /></a></td>
                    <td>{$story.title}</td>
                    <td>{$story.authors}</td>
                    <td>{$story.post_date}</td>
                    <td>NEW</td>
                  </tr>
              {/foreach}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <!-- /#page-content-wrapper -->

  </div>
  <!-- /#wrapper -->
{/block}