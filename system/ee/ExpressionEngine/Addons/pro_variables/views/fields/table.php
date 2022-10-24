<table data-input-name="<?=$name?>">
    <thead>
        <tr>
        <?php foreach ($cols as $col) : ?>
            <th scope="col"><?=htmlspecialchars($col)?></th>
        <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $rownum => $row) : ?>
        <tr>
        <?php foreach (array_keys($cols) as $i) : ?>
            <td>
                <input type="text" name="<?=$name?>[<?=$rownum?>][<?=$i?>]" value="<?=(isset($row[$i]) ? htmlspecialchars($row[$i], ENT_QUOTES) : '')?>" />
            </td>
        <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<ul class="toolbar">
    <li class="add"><a href="" title="<?=lang('add_row')?>"></a></li>
</ul>