<?=form_input($field)?>
<?php

$show_fmt = (isset($settings['field_show_fmt']) && $settings['field_show_fmt'] == 'y');
$show_file_selector = (isset($settings['field_show_file_selector']) && $settings['field_show_file_selector'] == 'y');

if ($show_fmt OR $show_file_selector): ?>
<div class="format-options">

	<?php if ($show_file_selector): ?>
	<ul class="toolbar">
		<li class="upload"><a class="m-link textarea-field-filepicker" href="<?=$fp_url?>" rel="modal-file" title="<?=lang('upload_file')?>" rel="modal-file" data-input-value="<?=$name?>"></a></li>
		<?php if ($show_fmt): ?>
		<li class="form-element">
			<?=form_dropdown('field_ft_'.$settings['field_id'], $format_options, $settings['field_fmt'])?>
		</li>
		<?php endif; ?>
	</ul>
	<?php endif; ?>

	<?php if ( ! $show_file_selector && $show_fmt): ?>
		<?=form_dropdown('field_ft_'.$settings['field_id'], $format_options, $settings['field_fmt'])?>
	<?php endif; ?>
</div>
<?php endif; ?>
