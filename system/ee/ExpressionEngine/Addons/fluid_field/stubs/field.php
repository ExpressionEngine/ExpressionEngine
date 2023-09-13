<?php $this->extend('_field_wrapper'); ?>
{<?=$field_name?>}
<?php foreach ($fluidFields as $fluidFieldName => $fluidField) : ?>
    {<?=$field_name . ':' . $fluidFieldName?>}
    <?=$this->embed($fluidField['stub'], $fluidField)?>
    {/<?=$field_name . ':' . $fluidFieldName?>}
<?php endforeach; ?>
<?php foreach ($fluidFieldGroups as $fluidFieldGroupName => $fluidFields) : ?>
    {<?=$field_name . ':' . $fluidFieldGroupName?>}
    {fields}
    <?php foreach ($fluidFields as $fluidFieldName => $fluidField) : ?>
        {<?=$field_name . ':' . $fluidFieldName?>}
        <?=$this->embed($fluidField['stub'], $fluidField)?>
        {/<?=$field_name . ':' . $fluidFieldName?>}
    <?php endforeach; ?>
    {/fields}
    {/<?=$field_name . ':' . $fluidFieldGroupName?>}
<?php endforeach; ?>
{/<?=$field_name?>}