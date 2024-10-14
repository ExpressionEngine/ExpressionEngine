{<?=$field_name?>}
<?php foreach ($fluidFields as $fluidFieldName => $fluidField) : ?>
    <?php if($show_comments ?? false): ?>

    {!-- Fluid Field: <?=$fluidField['field_label']?> --}
    {!-- Fieldtype: <?=$fluidField['field_type']?> --}
    {!-- Docs: <?=$fluidField['docs_url']?> --}
    <?php endif; ?>

    {<?=$field_name . ':' . $fluidFieldName?>}
        <?php $fluidField['field_name'] = 'content'; ?>

        <?=$this->embed($fluidField['stub'], $fluidField)?>

    {/<?=$field_name . ':' . $fluidFieldName?>}
    <?php if($show_comments ?? false): ?>

    {!-- End Fluid Field: <?=$fluidField['field_label']?> --}
    <?php endif; ?>
<?php endforeach; ?>
<?php foreach ($fluidFieldGroups as $fluidFieldGroupName => $fluidFields) : ?>
    <?php if($show_comments ?? false): ?>

    {!-- Fluid Field Group: <?=$fluidFieldGroupName?> --}
    <?php endif; ?>

    {<?=$field_name . ':' . $fluidFieldGroupName?>}
        {fields}
        <?php foreach ($fluidFields as $fluidFieldName => $fluidField) : ?>
            <?php if($show_comments ?? false): ?>

            {!-- Fluid Field: <?=$fluidField['field_label']?> --}
            {!-- Fieldtype: <?=$fluidField['field_type']?> --}
            {!-- Docs: <?=$fluidField['docs_url']?> --}
            <?php endif; ?>

            {<?=$field_name . ':' . $fluidFieldName?>}
                <?php $fluidField['field_name'] = 'content'; ?>

                <?=$this->embed($fluidField['stub'], $fluidField)?>

            {/<?=$field_name . ':' . $fluidFieldName?>}
            <?php if($show_comments ?? false): ?>

            {!-- End Fluid Field: <?=$fluidField['field_label']?> --}
            <?php endif; ?>
        <?php endforeach; ?>

        {/fields}
    {/<?=$field_name . ':' . $fluidFieldGroupName?>}
    <?php if($show_comments ?? false): ?>

    {!-- End Fluid Field Group: <?=$fluidFieldGroupName?> --}
    <?php endif; ?>
<?php endforeach; ?>

{/<?=$field_name?>}
