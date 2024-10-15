{<?=$field_name?>}
<?php foreach ($columns as $column) : ?>

    <?php if($show_comments ?? false): ?>

    {!-- Grid column: <?=$column['field_label']?> --}
    {!-- Column type: <?=$column['field_type']?> --}
    {!-- Docs: <?=$column['docs_url']?> --}
    <?php endif; ?>

    <?=$this->embed($column['stub'], $column)?>

    <?php if($show_comments ?? false): ?>

    {!-- End Grid column: <?=$column['field_label']?> --}
    <?php endif; ?>
<?php endforeach; ?>

{/<?=$field_name?>}
