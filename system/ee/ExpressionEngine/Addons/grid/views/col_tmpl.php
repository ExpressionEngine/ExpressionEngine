<div class="fields-grid-item<?=(! $column['col_type']) ? ' fields-grid-item---open' : ''?><?=isset($column['col_hidden']) && $column['col_hidden'] ? ' hidden' : ''?>" data-field-name="<?=$field_name?>">
	<?=$this->embed('grid:grid-col-tools', ['col_label' => $column['col_label'], 'col_type' => $column['col_type']])?>
	<div class="toggle-content">
		<div class="fields-grid-common">
			<?=$column['top_form']?>
		</div>
		<div class="grid-col-settings-custom">
			<?php if (isset($column['settings_form'])): ?>
				<?=$column['settings_form']?>
			<?php endif ?>
		</div>
		<?=$this->embed('grid:grid-col-tools')?>
	</div>
</div>
