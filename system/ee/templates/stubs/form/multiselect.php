<ul>
<?php foreach ($field_list_items as $value => $label) : ?>
    <li>
        <input type="checkbox" name="<?=$field_name?>[]" value="<?=$value?>" id="<?=$field_name?>__<?=urlencode($value)?>">
        <label for="<?=$field_name?>__<?=urlencode($value)?>"><?=$label?></label>
    </li>
<?php endforeach; ?>
</ul>