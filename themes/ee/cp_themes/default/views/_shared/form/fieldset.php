<?php

// If a string is passed, just display the string
if (is_string($setting))
{
	echo $setting;
	return;
}

// Gather classes needed to set on the fieldset
$fieldset_classes = '';
// Any fields required?
foreach ($setting['fields'] as $field_name => $field)
{
	if (isset($field['required']) && $field['required'] == TRUE)
	{
		$fieldset_classes .= ' required';
		break;
	}
}
if (isset($setting['security']) && $setting['security'] == TRUE)
{
	$fieldset_classes .= ' security-enhance';
}
if (isset($setting['caution']) && $setting['caution'] == TRUE)
{
	$fieldset_classes .= ' security-caution';
}
if ($setting == end($settings))
{
	$fieldset_classes .= ' last';
}

// Individual settings can have their own groups
$setting_group = $group;
if (isset($setting['group']))
{
	$setting_group = $setting['group'];
}

$grid = (isset($setting['grid']) && $setting['grid'] == TRUE);

// Grids have to be in a div for an overflow bug in Firefox
$element = ($grid) ? 'div' : 'fieldset'; ?>
<<?=$element?> class="col-group<?=$fieldset_classes?> <?=( ! $grid) ? form_error_class(array_keys($setting['fields'])) : '' ?> <?=($grid) ? 'grid-publish' : '' ?>" <?php if ($setting_group): ?> data-group="<?=$setting_group?>"<?php endif ?>>
	<div class="setting-txt col <?=($grid) ? form_error_class(array_keys($setting['fields'])) : '' ?> <?=(isset($setting['wide']) && $setting['wide'] == TRUE) ? 'w-16' : 'w-8'?>">
		<h3><?=lang($setting['title'])?></h3>
		<em><?=lang($setting['desc'])?></em>
	</div>
	<div class="setting-field col <?=(isset($setting['wide']) && $setting['wide'] == TRUE) ? 'w-16' : 'w-8'?> last">
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
</<?=$element?>>
