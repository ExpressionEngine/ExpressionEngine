<div class="grid-item" data-field-name="<?=$field_name?>">
	<div class="grid-fields">
		<fieldset class="col-group">
			<div class="setting-txt col w-16">
				<h3>Type</h3>
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
		<fieldset class="col-group">
			<div class="setting-txt col w-16">
				<h3>Label</h3>
				<em>Name of field that appears in the publish form.</em>
			</div>
			<div class="setting-field col w-16 last">
				<?=form_input('grid[cols]['.$field_name.'][col_label]', isset($column['col_label']) ? $column['col_label'] : '')?>
			</div>
		</fieldset>
		<fieldset class="col-group">
			<div class="setting-txt col w-16">
				<h3>Short name</h3>
				<em>Short name for this field.<br><i>No spaces. Underscores and dashes are allowed.</i></em>
			</div>
			<div class="setting-field col w-16 last">
				<?=form_input('grid[cols]['.$field_name.'][col_name]', isset($column['col_name']) ? $column['col_name'] : '')?>
			</div>
		</fieldset>
		<fieldset class="col-group">
			<div class="setting-txt col w-16">
				<h3>Instructions</h3>
				<em>Field instructions that appear in the publish form.</em>
			</div>
			<div class="setting-field col w-16 last">
				<?=form_input('grid[cols]['.$field_name.'][col_instructions]', isset($column['col_instructions']) ? $column['col_instructions'] : '')?>
			</div>
		</fieldset>
		<fieldset class="col-group">
			<div class="setting-txt col w-16">
				<h3>Is this field</h3>
				<em>Make this field required, or searchable.</em>
			</div>
			<div class="setting-field col w-16 last">
				<label class="choice block">
					<?=form_checkbox(
						'grid[cols]['.$field_name.'][col_required]',
						'column_required',
						(isset($column['col_required']) && $column['col_required'] == 'y')
					)?> Require field?
				</label>
				<label class="choice block">
					<?=form_checkbox(
						'grid[cols]['.$field_name.'][col_search]',
						'column_searchable',
						(isset($column['col_search']) && $column['col_search'] == 'y')
					)?> Include in search?
				</label>
			</div>
		</fieldset>
		<fieldset class="col-group last">
			<div class="setting-txt col w-16">
				<h3>Column Width</h3>
				<em>Set the width of this column in the publish form.</em>
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
	<?php if (isset($column['settings_form'])): ?>
		<?=$column['settings_form']?>
	<?php endif ?>
	<fieldset class="grid-tools">
		<ul class="toolbar">
			<li class="copy"><a href="" title="copy field"></a></li>
			<li class="add"><a href="" title="add new field"></a></li>
			<li class="remove"><a href="" title="remove field"></a></li>
		</ul>
	</fieldset>
</div><!-- /grid-item -->
