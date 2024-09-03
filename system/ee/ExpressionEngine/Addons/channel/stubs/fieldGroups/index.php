{!-- Field Group: <?=$field_group?> --}

<?php foreach ($fields as $field) : ?>


    {!-- Field: <?=$field['field_label']?> --}
    {!-- Fieldtype: <?=$field['field_type']?> --}
    {!-- Docs: <?=$field['docs_url']?> --}

    <?=$this->embed($field['stub'], $field);?>

    {!-- End field: <?=$field['field_label']?> --}


<?php endforeach; ?>

{!-- End Field Group: <?=$field_group?> --}
