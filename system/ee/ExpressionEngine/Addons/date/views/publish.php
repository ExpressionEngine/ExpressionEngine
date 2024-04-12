<?php if ($has_localize_option): ?>
    <?php if ($show_localize_options == 'localized') :
        echo form_hidden($localize_option_name, '');
    elseif ($show_localize_options == 'fixed') :
        echo form_hidden($localize_option_name, ee()->session->userdata('timezone', ee()->config->item('default_site_timezone')));
    ?>
    <?php else : ?>
    <?php $extra = ($disabled) ? 'disabled' : '' ?>
    <div class="field-option">
        <label class="checkbox-label">
            <?= form_radio($localize_option_name, '', ($localized == 'y'), $extra) ?>
            <div class="checkbox-label__text"><?=lang('localized_date')?></div>
        </label>
        <label class="checkbox-label">
            <?= form_radio($localize_option_name, ee()->session->userdata('timezone', ee()->config->item('default_site_timezone')), ($localized == 'n'), $extra) ?>
            <div class="checkbox-label__text"><?=lang('fixed_date')?></div>
        </label>
    </div>
    <?php endif;?>
<?php endif; ?>
<?php
$params = [
    'value' => $value,
    'name' => $field_name,
    'rel' => 'date-picker',
    'data-date-format' => $date_format,
    'autocomplete' => 'off',
    'data-include_time' => $include_time
];

if ($value) {
    $params['data-timestamp'] = ee()->localize->string_to_timestamp($value, ($localized == 'y'), $date_format);
}

if ($disabled) {
    $params['disabled'] = 'disabled';
}

echo form_input($params);
