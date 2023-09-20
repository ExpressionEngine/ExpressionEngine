{<?=$field_name?>}
<?php foreach ($columns as $column) : ?>
    {!-- Column type: <?=$column['field_type']?> --}
    {!-- Docs: <?=$column['docs_url']?> --}
    <?=$this->embed($column['stub'], $column)?>
<?php endforeach; ?>
{/<?=$field_name?>}