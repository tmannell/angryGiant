/* Angry Giant custom javascript */

/**
 * Highlights form fields when values do not pass validation.
 *
 * @param errors
 *  A json array of fields with error statuses
 */
function highlightErrors(errors) {
    $.each(errors, function (key, value) {
        if (key === 'password_1') {
            $("#password_1, #password_2").addClass(value);
        }
        else {
            $("#" + key).addClass(value);
        }
    });
}
/**
 * Show hide fields based on radio on/off button
 * @param trigger
 *  Trigger field (this)
 * @param target
 *  Target field.
 */
function showHideField(trigger, target) {

    if ($(trigger).val() !== '1') {
        $(target).fadeIn('slow')
    }
    else if ($(trigger).val() === '1') {
        $(target).fadeOut('slow');
    }
}

/**
 * Using pikaday.js adds calendar widget
 * to fields with id #datepicker.
 */
function addCalender() {
    var picker = new Pikaday({
        field: $('#datepicker')[0],
        format: 'MMM-DD-YYYY',
    });
}

/**
 * Ajax call to route /fetch/page-numbers, server returns
 * available pages numbers for specific story_id (sid)
 */
function getPageNumbers() {
    var sid = $('#story select').val();
    if (sid !== '0') {
        $.ajax({
            type: "POST",
            data: {'sid': sid},
            url: "/fetch/page-numbers",
            dataType: 'json',
            success: function (pageNumbers) {
                // Remove the 0 value from the '#story' field.
                $("#story select option[value='0']").remove();
                var pageSelect = $('#page-number-select');
                // Empty the page select, select box.
                pageSelect.empty();
                // And repopulate it with available page numbers.
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