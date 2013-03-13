
<?=form_label(lang('grid_config'))?><br>
<i class="instruction_text"><?=lang('grid_config_desc')?></i>

<div id="grid_settings">
	<div id="grid_col_settings_labels">
		<?=form_label(lang('grid_col_type'), NULL, array('class' => 'grid_col_setting_label grid_data_type'))?>
		<?=form_label(lang('grid_col_label'), NULL, array('class' => 'grid_col_setting_label'))?>
		<?=form_label(lang('grid_col_name'), NULL, array('class' => 'grid_col_setting_label'))?>
		<?=form_label(lang('grid_col_instr'), NULL, array('class' => 'grid_col_setting_label'))?>
		<?=form_label(lang('grid_col_options'), NULL, array('class' => 'grid_col_setting_label grid_data_search'))?>
	</div>

	<div id="grid_col_settings_container">

		<div id="grid_col_settings_container_inner">

			<div class="grid_col_settings">
				<div class="grid_col_settings_section grid_data_type alt">
					<?=form_dropdown('grid[cols][new][0][type]', $fieldtypes, NULL, 'class="grid_col_select"')?>

					<a href="#" class="grid_col_settings_delete" title="Delete Column">Delete Column</a>
				</div>
				<div class="grid_col_settings_section text">
					<?=form_input('grid[cols][new][0][name]')?>
				</div>
				<div class="grid_col_settings_section text alt">
					<?=form_input('grid[cols][new][0][label]')?>
				</div>
				<div class="grid_col_settings_section text">
					<?=form_input('grid[cols][new][0][instr]')?>
				</div>
				<div class="grid_col_settings_section grid_data_search alt">
					<?=form_checkbox('grid[cols][new][0][required]', 'column_required').form_label(lang('grid_col_required'))?>
					<?=form_checkbox('grid[cols][new][0][searchable]', 'column_searchable').form_label(lang('grid_col_searchable'))?>
				</div>
				<?php foreach ($settings as $key => $value): ?>
					<?php foreach ($value as $index => $setting): ?>
						<div class="grid_col_settings_section <?=($index % 2) ? 'alt' : ''?>">
							<?=$setting?>
						</div>
					<?php endforeach ?>
				<?php endforeach ?>
				<div class="grid_col_settings_section grid_col_copy">
					<a href="#" class="grid_col_copy">Copy</a>
				</div>
			</div>

			<a class="grid_button_add" href="#">Add Column</a>

		</div>
	</div>
</div>