<div class="grid_col_settings">
	<div class="grid_col_settings_section grid_data_type alt">
		<?=form_dropdown(
			'grid[cols]'.$field_name.'[type]',
			$fieldtypes,
			$column['col_type'],
			'class="grid_col_select"')?>

		<a href="#" class="grid_col_settings_delete" title="Delete Column">Delete Column</a>
	</div>
	<div class="grid_col_settings_section text">
		<?=form_input('grid[cols]'.$field_name.'[label]', $column['col_label'])?>
	</div>
	<div class="grid_col_settings_section text alt">
		<?=form_input('grid[cols]'.$field_name.'[name]', $column['col_name'])?>
	</div>
	<div class="grid_col_settings_section text">
		<?=form_input('grid[cols]'.$field_name.'[instr]', $column['col_instructions'])?>
	</div>
	<div class="grid_col_settings_section grid_data_search alt">
		<?=form_checkbox(
			'grid[cols]'.$field_name.'[required]',
			'column_required',
			($column['col_required'] == 'y')
		).form_label(lang('grid_col_required'))?>

		<?=form_checkbox(
			'grid[cols]'.$field_name.'[searchable]',
			'column_searchable',
			($column['col_search'] == 'y')
		).form_label(lang('grid_col_searchable'))?>
	</div>
	<?php foreach ($column['settings_form'] as $index => $setting): ?>
		<div class="grid_col_settings_section <?=($index % 2) ? 'alt' : ''?>">
			<?=$setting?>
		</div>
	<?php endforeach ?>
	<div class="grid_col_settings_section grid_col_copy">
		<a href="#" class="grid_col_copy">Copy</a>
	</div>
</div>