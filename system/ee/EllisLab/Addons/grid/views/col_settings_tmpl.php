<div class="grid_col_settings_custom_field_<?=$col_type?>" data-fieldtype="<?=$col_type?>">
	<?php foreach ($col_settings as $name => $settings)
	{
		foreach ($settings as &$setting)
		{
			if (is_array($setting))
			{
				$setting['wide'] = TRUE;
			}
		}
		$this->embed('ee:_shared/form/section', array('name' => $name, 'settings' => $settings));
	} ?>
</div>
