<div id="grid_settings_container">
	<div id="grid_settings">
		<div id="grid_col_settings_labels">
			<?=form_label(lang('grid_col_type'), NULL, array('class' => 'grid_col_setting_label grid_data_type'))?>
			<?=form_label(lang('grid_col_label'), NULL, array('class' => 'grid_col_setting_label'))?>
			<?=form_label(lang('grid_col_name'), NULL, array('class' => 'grid_col_setting_label'))?>
			<?=form_label(lang('grid_col_instr'), NULL, array('class' => 'grid_col_setting_label'))?>
			<?=form_label(lang('grid_col_options'), NULL, array('class' => 'grid_col_setting_label grid_data_search'))?>
			<?=form_label(lang('grid_col_width'), NULL, array('class' => 'grid_col_setting_label'))?>
		</div>

		<div id="grid_col_settings_container">

			<div id="grid_col_settings_container_inner" class="group">

				<?php foreach ($columns as $column): ?>
					<?=$column?>
				<?php endforeach ?>

				<a class="grid_button_add" href="#" title="<?=lang('grid_add_column')?>"><?=lang('grid_add_column')?></a>

			</div>
		</div>
	</div>

	<div id="grid_col_settings_elements">
		<?=$blank_col?>

		<?php foreach ($settings_forms as $form): ?>
			<?=$form?>
		<?php endforeach ?>
	</div>
</div>