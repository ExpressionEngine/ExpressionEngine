{<?=$field_name?>}
<?php foreach ($columns as $column) : ?>
    {!-- Grid column: <?=$column['field_label']?> --}
    {!-- Column type: <?=$column['field_type']?> --}
    {!-- Docs: <?=$column['docs_url']?> --}
    <?=$this->embed($column['stub'], $column)?>

    {!-- End Grid column: <?=$column['field_label']?> --}
<?php endforeach; ?>
{/<?=$field_name?>}
