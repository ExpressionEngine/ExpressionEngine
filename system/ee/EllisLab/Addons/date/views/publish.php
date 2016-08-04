<?php if ($has_localize_option): ?>
	<?php $extra = ($disabled) ? 'disabled' : '' ?>
<label class="choice mr<?php if ($localized == 'y') echo " chosen"; ?>">
	<?= form_radio($localize_option_name, 'y', ($localized == 'y'), $extra) ?>
	<?=lang('localized_date')?>
</label>
<label class="choice<?php if ($localized == 'n') echo " chosen"; ?>">
	<?= form_radio($localize_option_name, 'n', ($localized == 'n'), $extra) ?>
	<?=lang('fixed_date')?>
</label>
<?php endif; ?>
<?php
$params = array('value' => $value, 'name' => $field_name, 'rel' => 'date-picker');

if ($value)
{
	$params['data-timestamp'] = ee()->localize->string_to_timestamp($value, ($localized == 'y'), $date_format);
}

if ($disabled)
{
	$params['disabled']	= 'disabled';
}

echo form_input($params);
