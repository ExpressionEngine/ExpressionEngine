<?php if ($field_settings['lv_ft_multiple'] == 'n') : ?>
{<?=$field_name?>:var}
<?php else : ?>
<ul>
{<?=$field_name?>}
    <li aria-label="{item}">{{var}}</li>
{/<?=$field_name?>}
</ul>
<?php endif; ?>