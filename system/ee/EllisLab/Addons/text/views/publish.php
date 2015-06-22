<?=form_input($field)?>
<?php if (isset($settings['field_show_fmt']) && $settings['field_show_fmt'] == 'y'): ?>
<div class="format-options">
	<ul class="toolbar">
		<li class="form-element">
			<?=form_dropdown('field_ft_'.$settings['field_id'], $format_options, $settings['field_fmt'])?>
		</li>
	</ul>
</div>
<?php endif; ?>