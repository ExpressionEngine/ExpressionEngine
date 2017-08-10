<div class="fields-grid-item" data-field-name="<?=$field_name?>">
	<?=$this->embed('grid:grid-col-tools')?>
	<?=$column['top_form']?>
	<div class="grid-col-settings-custom">
		<?php if (isset($column['settings_form'])): ?>
			<?=$column['settings_form']?>
		<?php endif ?>
	</div>
	<?=$this->embed('grid:grid-col-tools')?>
</div>
