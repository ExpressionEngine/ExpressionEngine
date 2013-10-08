<div class="grid_col_settings" data-field-name="<?=$field_name?>">
	<div class="grid_col_settings_section grid_data_type alt">
		<?=form_dropdown(
			'grid[cols]['.$field_name.'][col_type]',
			$fieldtypes,
			isset($column['col_type']) ? $column['col_type'] : 'text',
			'class="grid_col_select"')?>

		<a href="#" class="grid_button_delete" title="<?=lang('grid_delete_column')?>"><?=lang('grid_delete_column')?></a>
	</div>
	<div class="grid_col_settings_section text">
		<?=form_input('grid[cols]['.$field_name.'][col_label]', isset($column['col_label']) ? $column['col_label'] : '', ' class="grid_col_field_label"')?>
	</div>
	<div class="grid_col_settings_section text alt">
		<?=form_input('grid[cols]['.$field_name.'][col_name]', isset($column['col_name']) ? $column['col_name'] : '', ' class="grid_col_field_name"')?>
	</div>
	<div class="grid_col_settings_section text">
		<?=form_input('grid[cols]['.$field_name.'][col_instructions]', isset($column['col_instructions']) ? $column['col_instructions'] : '')?>
	</div>
	<div class="grid_col_settings_section grid_data_search alt">
		<?=form_label(form_checkbox(
			'grid[cols]['.$field_name.'][col_required]',
			'column_required',
			(isset($column['col_required']) && $column['col_required'] == 'y')
		).lang('grid_col_required'))?>

		<?=form_label(form_checkbox(
			'grid[cols]['.$field_name.'][col_search]',
			'column_searchable',
			(isset($column['col_search']) && $column['col_search'] == 'y')
		).lang('grid_col_searchable'))?>
	</div>
	<div class="grid_col_settings_section grid_col_width">
		<?=form_input(array(
				'name'		=> 'grid[cols]['.$field_name.'][col_width]',
				'value'		=> (isset($column['col_width'])) ? $column['col_width'] : '',
				'class'		=> 'grid_input_text_small',
				'maxlength'	=> 3
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