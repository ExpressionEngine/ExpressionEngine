<?php $this->extend('_field_wrapper'); ?>
<div>
    {<?=$field_name?>}
        <a href="{path={segment_1}/details/{<?=$field_name?>:url_title}}">{<?=$field_name?>:title}</a>
    {/<?=$field_name?>}
</div>