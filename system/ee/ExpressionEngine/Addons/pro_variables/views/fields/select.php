<?php $multi = ! empty($multiple); ?>
<select name="<?=$name . ($multi ? '[]' : '')?>"<?php if ($multi) :
    ?> class="pro-select-multiple" multiple<?php
endif; ?>>
<?php foreach ($choices as $key => $val) : ?>
    <?php if (is_array($val) && ! empty($val)) : ?>
        <optgroup label="<?=htmlspecialchars($key, ENT_QUOTES)?>">
        <?php foreach ($val as $k => $v) : ?>
            <option value="<?=htmlspecialchars($k, ENT_QUOTES)?>"<?=(in_array($k, (array) $value) ? ' selected' : '') ?>>
                <?=$v?>
            </option>
        <?php endforeach; ?>
        </optgroup>
    <?php else : ?>
        <option value="<?=htmlspecialchars($key, ENT_QUOTES)?>"<?=(in_array($key, (array) $value) ? ' selected' : '') ?>>
            <?=$val?>
        </option>
    <?php endif; ?>
<?php endforeach; ?>
</select>
