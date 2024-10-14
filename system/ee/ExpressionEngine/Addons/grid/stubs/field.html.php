<?php if (count($columns) <= 3) : ?>
<table>
    <thead>
        <tr>
        <?php foreach ($columns as $column) : ?>
            <th><?=$column['field_label']?></th>
        <?php endforeach; ?>

        </tr>
    </thead>
    <tbody>
        {<?=$field_name?>}
        <tr>
        <?php foreach ($columns as $column) : ?>

            <td>
                <?php if($show_comments ?? false): ?>

                {!-- Grid column: <?=$column['field_label']?> --}
                {!-- Column type: <?=$column['field_type']?> --}
                {!-- Docs: <?=$column['docs_url']?> --}
                <?php endif; ?>

                <?=$this->embed($column['stub'], $column)?>

                <?php if($show_comments ?? false): ?>

                {!-- End Grid column: <?=$column['field_label']?> --}
                <?php endif; ?>

            </td>
        <?php endforeach; ?>

        </tr>
        {/<?=$field_name?>}
    </tbody>
</table>
<?php else : ?>

{<?=$field_name?>}
<?php foreach ($columns as $column) : ?>

    <?php if($show_comments ?? false): ?>
    {!-- Grid column: <?=$column['field_label']?> --}
    {!-- Column type: <?=$column['field_type']?> --}
    {!-- Docs: <?=$column['docs_url']?> --}
    <?php endif; ?>

    <h4><?=$column['field_label']?></h4>
    <?=$this->embed($column['stub'], $column)?>

    <?php if($show_comments ?? false): ?>
    {!-- End Grid column: <?=$column['field_label']?> --}
    <?php endif; ?>
<?php endforeach; ?>

{/<?=$field_name?>}

<?php endif; ?>
