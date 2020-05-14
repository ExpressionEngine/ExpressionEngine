<?php if ($has_localize_option): ?>
	<?php $extra = ($disabled) ? 'disabled' : '' ?>
	<div class="field-option">
		<label>
			<?= form_radio($localize_option_name, '', ($localized == 'y'), $extra) ?>
			<?=lang('localized_date')?>
		</label>
		<label>
			<?= form_radio($localize_option_name, ee()->session->userdata('timezone', ee()->config->item('default_site_timezone')), ($localized == 'n'), $extra) ?>
			<?=lang('fixed_date')?>
		</label>
	</div>
<?php endif; ?>
<?php
$params = [
	'value' => $value,
	'name' => $field_name,
	'rel' => 'date-picker',
	'data-date-format' => $date_format
];

if ($value)
{
	$params['data-timestamp'] = ee()->localize->string_to_timestamp($value, ($localized == 'y'), $date_format);
}

if ($disabled)
{
	$params['disabled']	= 'disabled';
}

echo form_input($params);
