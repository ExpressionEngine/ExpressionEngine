<ul class="pro-vars-in-group">
    <?php foreach ($vars as $row) : ?>
        <li>
            <input type="hidden" name="vars[]" value="<?=$row['variable_id']?>" />
            <span class="ico reorder"></span>
            <?=htmlspecialchars($row['variable_label'] ?: $row['variable_name'])?>
        </li>
    <?php endforeach; ?>
</ul>
