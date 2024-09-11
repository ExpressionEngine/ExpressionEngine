<?php if ($is_tag_pair) : ?>
{<?=$field_name?>}
    {!-- This field is built to be used as tag pair --}
    {!-- But we could not determine the possible variables to use inside tag pair --}
    {!-- Please refer to the documentation link above --}
{/<?=$field_name?>}
<?php else : ?>
{<?=$field_name . $modifiers_string?>}
<?php endif; ?>
