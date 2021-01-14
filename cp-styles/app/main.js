


$('body').on('click', 'a.toggle-btn', function (e) {
    if ($(this).hasClass('disabled')) {
        //||
        // $(this).parents('.toggle-tools').size() > 0 ||
        // $(this).parents('[data-reactroot]').size() > 0) {
        return;
    }

    var input = $(this).find('input[type="hidden"]'),
        yes_no = $(this).hasClass('yes_no'),
        onOff = $(this).hasClass('off') ? 'on' : 'off',
        trueFalse = $(this).hasClass('off') ? 'true' : 'false';

    if ($(this).hasClass('off')){
        $(this).removeClass('off');
        $(this).addClass('on');
        $(input).val(yes_no ? 'y' : 1);
    } else {
        $(this).removeClass('on');
        $(this).addClass('off');
        $(input).val(yes_no ? 'n' : 0);
    }

    $(this).attr('alt', onOff);
    $(this).attr('data-state', onOff);
    $(this).attr('aria-checked', trueFalse);

    if ($(input).data('groupToggle')) EE.cp.form_group_toggle(input)

    e.preventDefault();
});
