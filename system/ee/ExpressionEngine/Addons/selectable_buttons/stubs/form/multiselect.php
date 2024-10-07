<div class="button-group selectable_buttons ">
    <input type="hidden" name="{<?=$field_name?>}">
    <?php foreach ($field_list_items as $value => $label) : ?>
    <label class="button button--default">
        <input class="hidden" type="checkbox" name="<?=$field_name?>[]" value="<?=$value?>">
        <div class="checkbox-label__text"><?=$label?></div>
    </label>
    <?php endforeach; ?>
</div>


<script>
$('body').on('change','.selectable_buttons .button input[type="checkbox"]', function (e) {

    if ( !($(this).parents('.button-group').hasClass('multiple')) ) {
        var elParent = $(this).parents('.selectable_buttons');
        $(elParent).find('.button input[type="checkbox"]').not(this).prop('checked', false);
    }

    $(this).parents('.button-group').find('.button input[type="checkbox"]').each(function () {
        if ($(this).prop('checked')) {
            $(this).parent().addClass('active');
        } else {
            $(this).parent().removeClass('active')
        }
    });
});
</script>