$(document).ready(function () {

    // Submit forms with an inline link
    $('.row-controls a.submit, .table-controls a.submit').click(function (event) {
        event.preventDefault();
        $('form.structure-form').submit();
    });

    // Swap channel type settings options
    $(".type-picker select").change(function () {
        $(this).parents('tr').find('td .active').removeClass('active');
        $(this).parents('tr').find('td .' + $(this).val()).addClass('active');
        if ($(this).val() == 'page') {
            $(this).parents('td').find('.option').addClass('active');
        }
    });

});