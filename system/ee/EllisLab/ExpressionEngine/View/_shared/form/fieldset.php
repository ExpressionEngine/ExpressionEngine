<?php

// If a string is passed, just display the string
if (is_string($setting))
{
	echo $setting;
	return;
}

$grid = (isset($setting['grid']) && $setting['grid'] == TRUE);

// Gather classes needed to set on the fieldset
$fieldset_classes = '';

// First, see if there are any specified in the attributes array
if (isset($setting['attrs']['class']))
{
	$fieldset_classes = ' ' . $setting['attrs']['class'];
	unset($setting['attrs']['class']);
}

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
if (isset($setting['hide']) && $setting['hide'] == TRUE)
{
	$fieldset_classes .= ' hidden';
}
if ($grid)
{
	$fieldset_classes .= ' grid-publish';
}
else
{
	$fieldset_classes .= ' '.form_error_class(array_keys($setting['fields']));
}
// If a validation result object is set, see if any of our fields have errors
if (isset($errors))
{
	foreach (array_keys($setting['fields']) as $field)
	{
		if ($errors->hasErrors($field))
		{
			$fieldset_classes .= ' invalid';
			break;
		}
	}
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

// Grids have to be in a div for an overflow bug in Firefox
$element = ($grid) ? 'div' : 'fieldset'; ?>
<<?=$element?> class="col-group<?=$fieldset_classes?>" <?php if ($setting_group): ?> data-group="<?=$setting_group?>"<?php endif ?><?php if (isset($setting['attrs'])): foreach ($setting['attrs'] as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; endif; ?>>
	<div class="setting-txt col <?=($grid) ? form_error_class(array_keys($setting['fields'])) : '' ?> <?=(isset($setting['wide']) && $setting['wide'] == TRUE) ? 'w-16' : 'w-8'?>">
		<?php if (isset($setting['title'])): ?>
		<h3><?=lang($setting['title'])?></h3>
		<?php endif; ?>
		<?php if (isset($setting['desc'])): ?>
		<em><?=lang($setting['desc'])?></em>
		<?php endif; ?>
		<?php if (isset($setting['desc_cont'])): ?>
		<em><?=lang($setting['desc_cont'])?></em>
		<?php endif; ?>
		<?php if (isset($setting['example'])): ?>
		<p><?=$setting['example']?></p>
		<?php endif; ?>
		<?php if (isset($setting['button'])): ?>
		<?php
			$button = $setting['button'];
			$rel = isset($button['rel']) ? $button['rel'] : '';
		?>
		<p><button class="btn action submit mf-link" type="button" rel="<?=$rel?>"><?=lang($button['text'])?></button></p>
		<?php endif; ?>
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

				$this->embed('ee:_shared/form/field', $vars);
			}
		?>
	</div>
</<?=$element?>>
