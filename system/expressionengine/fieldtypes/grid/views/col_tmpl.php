<div class="grid_col_settings">
	<div class="grid_col_settings_section grid_data_type alt">
		<?=form_dropdown(
			'grid[cols]['.$field_name.'][type]',
			$fieldtypes,
			isset($column['col_type']) ? $column['col_type'] : 'text',
			'class="grid_col_select"')?>

		<a href="#" class="grid_button_delete" title="<?=lang('grid_delete_column')?>"><?=lang('grid_delete_column')?></a>
	</div>
	<div class="grid_col_settings_section text">
		<?=form_input('grid[cols]['.$field_name.'][label]', isset($column['col_label']) ? $column['col_label'] : '')?>
	</div>
	<div class="grid_col_settings_section text alt">
		<?=form_input('grid[cols]['.$field_name.'][name]', isset($column['col_name']) ? $column['col_name'] : '')?>
	</div>
	<div class="grid_col_settings_section text">
		<?=form_input('grid[cols]['.$field_name.'][instr]', isset($column['col_instructions']) ? $column['col_instructions'] : '')?>
	</div>
	<div class="grid_col_settings_section grid_data_search alt">
		<?=form_checkbox(
			'grid[cols]['.$field_name.'][required]',
			'column_required',
			(isset($column['col_label']) && $column['col_required'] == 'y')
		).form_label(lang('grid_col_required'))?>

		<?=form_checkbox(
			'grid[cols]['.$field_name.'][searchable]',
			'column_searchable',
			(isset($column['col_label']) && $column['col_search'] == 'y')
		).form_label(lang('grid_col_searchable'))?>
	</div>
	<div class="grid_col_settings_section grid_col_width">
		<?=form_input(array(
				'name'	=> 'grid[cols]['.$field_name.'][width]',
				'value'	=> (isset($column['col_width'])) ? $column['col_width'] : '',
				'class'	=> 'grid_input_text_small'
			)).NBS.NBS.NBS.
			'<i class="instruction_text">'.lang('grid_col_width_percent').'</i>';?>
	</div>
	<div class="grid_col_settings_custom" data-field-name="<?=$field_name?>">
		<?php if (isset($column['settings_form'])): ?>
			<?=$column['settings_form']?>
		<?php endif ?>
	</div>
	<div class="grid_col_settings_section grid_col_copy">
		<a href="#" class="grid_col_copy"><?=lang('grid_copy_column')?></a>
	</div>
</div>