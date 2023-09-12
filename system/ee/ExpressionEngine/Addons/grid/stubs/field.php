<?php $this->extend('_field_wrapper'); ?>
{<?=$field_name?>}
<?php foreach ($columns as $column) : ?>
    <?=$this->embed($column['stub'], $column)?>
<?php endforeach; ?>
{/<?=$field_name?>}