{extends file="StatusMessage.tpl"}
{block name="content"}
    <form {$formAttr}>
      <div class="row">
        {if $op == 'add' || $op == 'edit'}
          <div class="col-sm-4 offset-sm-2">
            <div id="page-picture" class="form-group">
              <label>{$elements.pagePicture.label}</label>
              <span class="required">*</span>
              {$elements.pagePicture.html}
            </div>
            <div id="description" class="form-group">
              <label>{$elements.description.label}</label>
              {$elements.description.html}
            </div>
            <div id="story" class="form-group">
              <label>{$elements.story.label}</label>
              <span class="required">*</span>
              <div class="clearfix"></div>
              {$elements.story.html}
            </div>
            <div id="page-number" class="form-group">
              <label>{$elements.pageNumber.label}</label>
              <span class="required">*</span>
              <div class="clearfix"></div>
              {$elements.pageNumber.html}
            </div>
            <div id="publish" class="form-group">
              {foreach $elements.publish as $radio}
                {if $radio.label != ''}
                  <legend class="col-form-legend">{$radio.label}</legend>
                {/if}
                <div class="publish form-check form-check-inline form-check-label">{$radio.html}</div>
              {/foreach}
            </div>
            <div id="date-container">
              <div id="date" class="form-group" style="display:none;">
                <label>{$elements.date.label}</label>
                {$elements.date.html}
              </div>
            </div>
          </div>
        {/if}
        {if $op == 'edit' || $op == 'delete'}
          <div class="col-sm-4 {if $op == 'delete'}offset-sm-2{/if}">
            <div id="page-thumb"><img class="thumbnail" src="/pictures/thumbnail/{$filename}" alt="{$title}" /></div>
          </div>
        {/if}
      </div>
      <div class="row">
        <div class="col-sm-4 offset-sm-2">
          <div id="submit-btns">
            {$elements.btnSubmit.html}
            {$elements.btnCancel.html}
          </div>
        </div>
      </div>
    </form>


  <script type="text/javascript">
    var errors = {$errors}
    {literal}
      $(document).ready(function() {
          highlightErrors();
          getPageNumbers();
          $(".publish input:radio").click(function() {
              if ($(this).val() !== '1') {
                  $('#date').fadeIn('slow')
              }
              else if($(this).val() === '1') {
                  $('#date').fadeOut('slow');
              }
          });

          $('#story select').change(function() {
              getPageNumbers();
          })

          var picker = new Pikaday({
              field: $('#datepicker')[0],
              format: 'MMM-DD-YYYY',
          });
      });

      function getPageNumbers() {
          var sid = $('#story select').val();
          if (sid !== '0') {
              $.ajax({
                  type: "POST",
                  data: {'sid': sid},
                  url: "/fetch/page-numbers",
                  dataType: 'json',
                  success: function (pageNumbers) {
                      $("#story select option[value='0']").remove();
                      var pageSelect = $('#page-number-select');
                      pageSelect.empty();
                      $(pageNumbers).each(function (index, value) {
                          var option = $("<option/>").attr("value", value).text(value);
                          pageSelect.append(option);
                      });
                  },
                  error: function () {
                      alert('Something happened when retrieving page numbers.');
                  }
              });
          }
      }

      function highlightErrors() {
          $.each(errors, function(key, value) {
              if (key === 'password_1') {
                  $("#password_1, #password_2").addClass(value);
              }
              else {
                  $("#" + key).addClass(value);
              }
          });
      }
    {/literal}
  </script>
{/block}