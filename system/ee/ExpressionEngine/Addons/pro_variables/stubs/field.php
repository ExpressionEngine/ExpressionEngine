<?php if ($field_settings['lv_ft_multiple'] == 'n') : ?>
{<?=$field_name?>:var}
<?php else : ?>
{<?=$field_name?>}
    {{var}}
{/<?=$field_name?>}
<?php endif; ?>