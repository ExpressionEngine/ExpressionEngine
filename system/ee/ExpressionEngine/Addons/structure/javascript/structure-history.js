$(document).ready(function () {
    $('.nav_history_table td.current').parent().addClass('current');
    $('.nav_history_table td.restored').parent().addClass('restored');

    // If we have a restored nav in the table and the current and restored are
    // not the same, mark all the items between current and restored as skipped.
    if ($('.nav_history_table tr.restored').length > 0 && !$('.nav_history_table tr.restored').hasClass('current')) {
        var markSkipped = false;
        var skippedText = $('.nav_history_table').data('skipped');

        $('.nav_history_table tr').each(function () {
            if ($(this).hasClass('current')) {
                markSkipped = true;
            } else if (markSkipped === true) {
                if ($(this).hasClass('restored')) {
                    return false;
                }

                $(this).addClass('skipped');
                $(this).find('.status_col').html(skippedText);
            }
        });
    }
});