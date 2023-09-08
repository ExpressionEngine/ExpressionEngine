<?php $this->extend('_field_wrapper'); ?>
<ul>
{<?=$field_name?>}
    <li aria-label="{item}">{item:label} ({item:value})</li>
{/<?=$field_name?>}
</ul>