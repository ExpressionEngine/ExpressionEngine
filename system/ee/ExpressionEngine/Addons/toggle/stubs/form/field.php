<button type="button" class="toggle-btn {if <?=$field_name?>}on{if:else}off{/if}" data-toggle-for="<?=$field_name?>" data-state="{if <?=$field_name?>}on{if:else}off{/if}" role="switch" aria-checked="{if <?=$field_name?>}true{if:else}false{/if}" alt="{if <?=$field_name?>}on{if:else}off{/if}">
    <input type="hidden" name="<?=$field_name?>" value="{if <?=$field_name?>}1{if:else}0{/if}">
    <span class="slider"></span>
</button>
<script type="text/javascript">
$('body').on('click', 'button.toggle-btn', function (e) {
    if ($(this).hasClass('disabled') ||
        $(this).parents('.toggle-tools').length > 0 ||
        $(this).parents('[data-reactroot]').length > 0) {
        return;
    }

    var input = $(this).find('input[type="hidden"]'),
        yes_no = $(this).hasClass('yes_no'),
        onOff = $(this).hasClass('off') ? 'on' : 'off',
        trueFalse = $(this).hasClass('off') ? 'true' : 'false';

    if ($(this).hasClass('off')){
        $(this).removeClass('off');
        $(this).addClass('on');
        $(input).val(yes_no ? 'y' : 1).trigger('change');
    } else {
        $(this).removeClass('on');
        $(this).addClass('off');
        $(input).val(yes_no ? 'n' : 0).trigger('change');
    }

    $(this).attr('alt', onOff);
    $(this).attr('data-state', onOff);
    $(this).attr('aria-checked', trueFalse);

    e.preventDefault();
});
</script>