<?php 
    if (isset($var_type) && $var_type == 'Select Entries'):
        $component = [
            'items' => $items,
            'selected' => $value,
            'multi' => $multiple,
            'filter_url' => $filter_url,
            'limit' => $limit ? $limit : 100,
            'no_results' => lang('no_entries_found'),
            'button_label' => null,
            'can_add_items' => false,
            'can_edit_items' => false,
            'channels' => $channels,
            'display_entry_id' => false,
            'display_status' => false,
            'rel_max' => 0,
            'lang' => $lang,
        ];

        $placeholder = '<label class="field-loading">' . lang('loading') . '<span></span></label>';
 ?>
    <div data-relationship-react="<?=base64_encode(json_encode($component))?>" data-input-value="<?=$name?>">
        <div class="fields-select">
            <div class="field-inputs">
                <?php echo $placeholder ?>
            </div>
        </div>
    </div>
<?php else: ?>
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

<?php endif; ?>
