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
                {!-- Grid column: <?=$column['field_label']?> --}
                {!-- Column type: <?=$column['field_type']?> --}
                {!-- Docs: <?=$column['docs_url']?> --}
                <?=$this->embed($column['stub'], $column)?>

                {!-- End Grid column: <?=$column['field_label']?> --}
            </td>
        <?php endforeach; ?>
        </tr>
        {/<?=$field_name?>}
    </tbody>
</table>
<?php else : ?>
{<?=$field_name?>}
<?php foreach ($columns as $column) : ?>
    {!-- Grid column: <?=$column['field_label']?> --}
    {!-- Column type: <?=$column['field_type']?> --}
    {!-- Docs: <?=$column['docs_url']?> --}
    <?=$this->embed($column['stub'], $column)?>

    {!-- End Grid column: <?=$column['field_label']?> --}
<?php endforeach; ?>
{/<?=$field_name?>}
<?php endif; ?>