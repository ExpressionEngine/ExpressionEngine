{<?=$field_name?>}
<?php foreach ($fluidFields as $fluidFieldName => $fluidField) : ?>
    {!-- Fluid Field: <?=$fluidField['field_label']?> --}
    {!-- Fieldtype: <?=$fluidField['field_type']?> --}
    {!-- Docs: <?=$fluidField['docs_url']?> --}
    {<?=$field_name . ':' . $fluidFieldName?>}
    <?=$this->embed($fluidField['stub'], $fluidField)?>

    {/<?=$field_name . ':' . $fluidFieldName?>}
    {!-- End Fluid Field: <?=$fluidField['field_label']?> --}
<?php endforeach; ?>
<?php foreach ($fluidFieldGroups as $fluidFieldGroupName => $fluidFields) : ?>
    {!-- Fluid Field Group: <?=$fluidFieldGroupName?> --}
    {<?=$field_name . ':' . $fluidFieldGroupName?>}
    {fields}
    <?php foreach ($fluidFields as $fluidFieldName => $fluidField) : ?>
        {!-- Fluid Field: <?=$fluidField['field_label']?> --}
        {!-- Fieldtype: <?=$fluidField['field_type']?> --}
        {!-- Docs: <?=$fluidField['docs_url']?> --}
        {<?=$field_name . ':' . $fluidFieldName?>}
        <?=$this->embed($fluidField['stub'], $fluidField)?>

        {/<?=$field_name . ':' . $fluidFieldName?>}
        {!-- End Fluid Field: <?=$fluidField['field_label']?> --}
    <?php endforeach; ?>
    {/fields}
    {/<?=$field_name . ':' . $fluidFieldGroupName?>}
    {!-- End Fluid Field Group: <?=$fluidFieldGroupName?> --}
<?php endforeach; ?>
{/<?=$field_name?>}