<div class="grid_col_settings_custom_field_<?=$col_type?>" data-fieldtype="<?=$col_type?>">
	<?php foreach ($col_settings as $name => $settings) {
    $this->embed('ee:_shared/form/section', ['name' => $name, 'settings' => $settings]);
} ?>
</div>
