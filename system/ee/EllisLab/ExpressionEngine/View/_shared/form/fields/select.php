<?php
$too_many = 8;

if (count($choices) == 0) return;

$nested = isset($nested) ? $nested : FALSE;
$encode = isset($encode) ? $encode : TRUE;

// Normalize choices into an array to keep order of items, order cannot be
// counted on in a JavaScript object
$normalized_choices = ee('View/Helpers')->normalizedChoices($choices, $nested);

$has_groupings = FALSE;
foreach ($normalized_choices as $key => $choice)
{
	if (isset($choice['section']))
	{
		$has_groupings = TRUE;
	}
	if (isset($choice['children']))
	{
		$nested = TRUE;
	}
}

// If it's a small list, just render it server-side
if (ee('View/Helpers')->countChoices($normalized_choices) <= $too_many && ! $nested && ! $has_groupings):
	// For radios with no value, set value to first choice
	if ( ! $multi && ! $value) {
		$keys = array_keys($choices);
		$value = $keys[0];
	}
	if ( ! isset($scalar) && $multi) $field_name .= '[]';
	?>
	<div class="fields-select">
		<div class="field-inputs">
			<?php foreach ($choices as $key => $choice):
				$label = isset($choice['label'])
					? lang($choice['label']) : lang($choice);
				$key = isset($choice['value'])
					? lang($choice['value']) : lang($key);
				$instructions = isset($choice['instructions']) ? $choice['instructions'] : NULL;
				if ($encode)
				{
					$label = ee('Format')->make('Text', lang($label))->convertToEntities();
					$instructions = ee('Format')->make('Text', $instructions)->convertToEntities();
				}
				$checked = ((is_bool($value) && get_bool_from_string($key) === $value)
					OR ( is_array($value) && in_array($key, $value))
					OR ( ! is_bool($value) && $key == $value)); ?>

				<label<?php if ($checked): ?> class="act"<?php endif ?>>
					<input type="<?=($multi) ? 'checkbox' : 'radio'?>" name="<?=$field_name?>" value="<?=htmlentities($key, ENT_QUOTES, 'UTF-8')?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=isset($attrs) ? $attrs : ''?>> <?=$label?>
						<?php if ($instructions): ?><i><?=$instructions?></i><?php endif ?>
				</label>
			<?php endforeach; ?>
		</div>
	</div>
<?php
// Large list, render it using React
else:
	if ($value && ! is_array($value) && ! $multi)
	{
		$label = ee('View/Helpers')->findLabelForValue($value, $normalized_choices);
		$value = [$value => $label];
	}
	elseif ($multi && ! is_array($value))
	{
		$value = [$value];
	}

	$component = [
		'name' => $field_name,
		'items' => $normalized_choices,
		'selected' => $value,
		'multi' => $multi,
		'nested' => $nested,
		'disabled' => isset($disabled) ? $disabled : FALSE,
		'autoSelectParents' => isset($auto_select_parents) ? $auto_select_parents : NULL,
		'tooMany' => $too_many,
		'filterUrl' => isset($filter_url) ? $filter_url : NULL,
		'limit' => isset($limit) ? $limit : 100,
		'toggleAll' => NULL,
		'groupToggle' => isset($group_toggle) ? $group_toggle : NULL,
		'manageable' => isset($manageable) ? $manageable : NULL,
		'manageLabel' => isset($manage_label) ? $manage_label : NULL,
		'reorderAjaxUrl' => isset($reorder_ajax_url) ? $reorder_ajax_url : NULL,
		'noResults' => isset($no_results['text']) ? lang($no_results['text']) : NULL
	];
	?>
	<div data-select-react="<?=base64_encode(json_encode($component))?>" data-input-value="<?=$field_name?>">
		<div class="fields-select">
			<div class="field-inputs">
				<label class="field-loading">
					<?=lang('loading')?><span></span>
				</label>
			</div>
		</div>
	</div>
<?php endif ?>
