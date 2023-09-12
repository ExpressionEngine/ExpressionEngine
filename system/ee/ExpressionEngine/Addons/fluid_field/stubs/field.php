<?php $this->extend('_field_wrapper'); ?>
{<?=$field_name?>}
<?php foreach ($fluidFields as $fluidField) : ?>
    {content}
    <?=$this->embed($fluidField['stub'], $fluidField)?>
    {/content}
<?php endforeach; ?>
{/<?=$field_name?>}