<ul class="radio-btn-wrap">
<?php foreach ($field_list_items as $value => $label) : ?>
    <li>
        <label for="<?=$field_name?>__<?=urlencode($value)?>">
            <input type="radio" name="<?=$field_name?>[]" value="<?=$value?>" id="<?=$field_name?>__<?=urlencode($value)?>"<?php if ($value == $field_value) echo ' checked'?>>
            <span><?=$label?></span>
        </label>
    </li>
<?php endforeach; ?>
</ul>