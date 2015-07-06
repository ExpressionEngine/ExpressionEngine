<div class="grid_col_settings_custom_field_<?=$col_type?>" data-fieldtype="<?=$col_type?>">
	<?php foreach ($col_settings as $name => $setting): ?>
		<?php if (is_string($name)): ?>
			<h2<?php if ($group): ?> data-group="<?=$group?>"<?php endif ?>><?=lang($name)?></h2>
		<?php endif; continue ?>
		<div class="setting-txt col w-16">
			<h3><?=lang($setting['title'])?></h3>
			<em><?=lang($setting['desc'])?></em>
		</div>
		<div class="setting-field col w-16">
			<?php
				foreach ($setting['fields'] as $field_name => $field)
				{
					$vars = array(
						'field_name' => $field_name,
						'field' => $field,
						'setting' => $setting,
						'grid' => $grid
					);

					$this->ee_view('_shared/form/field', $vars);
				}
			?>
		</div>
	<?php endforeach ?>
</div>
