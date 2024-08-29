{!-- Field Group: <?=$field_group?> --}

<?php foreach ($fields as $field) : ?>

<div>
    {!-- Field: <?=$field['field_label']?> --}
    {!-- Fieldtype: <?=$field['field_type']?> --}
    {!-- Docs: <?=$field['docs_url']?> --}
    <h4><?=$field['field_label']?></h4>
    <?=$this->embed($field['stub'], $field);?>

    {!-- End field: <?=$field['field_label']?> --}
</div>

<?php endforeach; ?>
