<div class="pro-drag-lists<?php if (isset($thumbs) && $thumbs) :
    ?> pro-thumbs<?php
endif;?>" data-name="<?=$name?>[]">
    <ul class="pro-off">
        <?php foreach ($choices as $key => $val) : ?>
            <?php if (! in_array($key, (array) $value)) : ?>
                <li><input type="hidden" value="<?=htmlspecialchars($key, ENT_QUOTES)?>"><?=$val?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
    <ul class="pro-on">
        <?php foreach ($value as $key) : ?>
            <?php if (array_key_exists($key, $choices)) : ?>
                <li><input type="hidden" name="<?=$name?>[]" value="<?=htmlspecialchars($key, ENT_QUOTES)?>"><?=$choices[$key]?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>
