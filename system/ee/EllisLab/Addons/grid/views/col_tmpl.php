<div class="grid-item" data-field-name="<?=$field_name?>">
	<div class="grid-fields">
		<fieldset class="col-group">
			<div class="setting-txt col w-16">
				<h3><?=lang('type')?></h3>
				<em></em>
			</div>
			<div class="setting-field col w-16 last">
				<?=form_dropdown(
					'grid[cols]['.$field_name.'][col_type]',
					$fieldtypes,
					isset($column['col_type']) ? $column['col_type'] : 'text',
					'class="grid_col_select"')?>
			</div>
		</fieldset>
		<fieldset class="col-group<?php if (in_array('grid[cols]['.$field_name.'][col_label]', $error_fields)): ?> invalid<?php endif ?>">
			<div class="setting-txt col w-16">
				<h3><?=lang('label')?></h3>
				<em><?=lang('label_desc')?></em>
			</div>
			<div class="setting-field col w-16 last">
				<?=form_input('grid[cols]['.$field_name.'][col_label]', isset($column['col_label']) ? $column['col_label'] : '', ' class="grid_col_field_label"')?>
			</div>
		</fieldset>
		<fieldset class="col-group<?php if (in_array('grid[cols]['.$field_name.'][col_name]', $error_fields)): ?> invalid<?php endif ?>">
			<div class="setting-txt col w-16">
				<h3><?=lang('short_name')?></h3>
				<em><?=lang('alphadash_desc')?></i></em>
			</div>
			<div class="setting-field col w-16 last">
				<?=form_input('grid[cols]['.$field_name.'][col_name]', isset($column['col_name']) ? $column['col_name'] : '', ' class="grid_col_field_name"')?>
			</div>
		</fieldset>
		<fieldset class="col-group<?php if (in_array('grid[cols]['.$field_name.'][col_instructions]', $error_fields)): ?> invalid<?php endif ?>">
			<div class="setting-txt col w-16">
				<h3><?=lang('instructions')?></h3>
				<em><?=lang('instructions_desc')?></em>
			</div>
			<div class="setting-field col w-16 last">
				<?=form_input('grid[cols]['.$field_name.'][col_instructions]', isset($column['col_instructions']) ? $column['col_instructions'] : '')?>
			</div>
		</fieldset>
		<fieldset class="col-group">
			<div class="setting-txt col w-16">
				<h3><?=lang('grid_in_this_field')?></h3>
				<em><?=lang('grid_in_this_field_desc')?></em>
			</div>
			<div class="setting-field col w-16 last">
				<?php
				$col_required = (isset($column['col_required']) && $column['col_required'] == 'y');
				$col_search = (isset($column['col_search']) && $column['col_search'] == 'y'); ?>
				<label class="choice block<?php if ($col_required): ?> chosen<?php endif ?>">
					<?=form_checkbox(
						'grid[cols]['.$field_name.'][col_required]',
						'column_required',
						$col_required
					)?> <?=lang('require_field')?>
				</label>
				<label class="choice block<?php if ($col_search): ?> chosen<?php endif ?>">
					<?=form_checkbox(
						'grid[cols]['.$field_name.'][col_search]',
						'column_searchable',
						$col_search
					)?> <?=lang('include_in_search')?>
				</label>
			</div>
		</fieldset>
		<fieldset class="col-group last<?php if (in_array('grid[cols]['.$field_name.'][col_width]', $error_fields)): ?> invalid<?php endif ?>">
			<div class="setting-txt col w-16">
				<h3><?=lang('grid_col_width')?></h3>
				<em><?=lang('grid_col_width_desc')?></em>
			</div>
			<div class="setting-field col w-16 last">
				<?=form_input(array(
					'name'		=> 'grid[cols]['.$field_name.'][col_width]',
					'value'		=> (isset($column['col_width'])) ? $column['col_width'] : '',
					'class'		=> 'grid_input_text_small',
					'maxlength'	=> 3
				))?>
			</div>
		</fieldset>
	</div>
	<div class="grid-col-settings-custom">
		<?php if (isset($column['settings_form'])): ?>
			<?=$column['settings_form']?>
		<?php endif ?>
	</div>
	<fieldset class="grid-tools">
		<ul class="toolbar">
			<li class="reorder"><a href="" title="<?=lang('grid_reorder_field')?>"></a></li>
			<li class="copy"><a href="" title="<?=lang('grid_copy_field')?>"></a></li>
			<li class="add"><a href="" title="<?=lang('grid_add_field')?>"></a></li>
			<li class="remove"><a href="" title="<?=lang('grid_remove_field')?>"></a></li>
		</ul>
	</fieldset>
</div><!-- /grid-item -->
