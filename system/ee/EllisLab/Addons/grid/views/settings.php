<div class="grid-wrap" data-group="grid">
	<div class="grid-label">
		<label class="label-type"><?=lang('grid_col_type')?></label>
		<label><?=lang('grid_col_label')?></label>
		<label><?=lang('grid_col_name')?></label>
		<label><?=lang('grid_col_instr')?></label>
		<label class="label-data"><?=lang('grid_col_options')?></label>
		<label><?=lang('grid_col_width')?></label>
	</div>
	<div class="grid-clip">
		<div class="grid-clip-inner">
			<?php foreach ($columns as $column): ?>
				<?=$column?>
			<?php endforeach ?>
		</div><!-- /grid-clip-inner -->
	</div><!-- /grid-clip -->
</div><!-- /grid-wrap -->

<div id="grid_col_settings_elements" data-group="always-hidden" class="hidden">
	<?=$blank_col?>

	<?php foreach ($settings_forms as $form): ?>
		<?=$form?>
	<?php endforeach ?>
</div>
