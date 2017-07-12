<?php
$too_many = 8;

if (count($choices) == 0) return;

// If it's a small list, just render it server-side
if (count($choices) <= $too_many):
	// For radios with no value, set value to first choice
	if ( ! $multi && ! $value) {
		$keys = array_keys($choices);
		$value = $keys[0];
	}
	?>
	<div class="fields-select">
		<div class="field-inputs">
			<?php foreach ($choices as $key => $choice):
				$label = isset($choice['label']) ? $choice['label'] : $choice;
				$checked = ((is_bool($value) && get_bool_from_string($key) === $value)
					OR ( is_array($value) && in_array($key, $value))
					OR ( ! is_bool($value) && $key == $value)); ?>

				<label<?php if ($checked): ?> class="act"<?php endif ?>>
					<input type="<?=($multi) ? 'checkbox' : 'radio'?>" name="<?=$field_name?>" value="<?=htmlentities($key, ENT_QUOTES, 'UTF-8')?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=isset($attrs) ? $atts : '' ?>> <?=lang($label)?>
				</label>
			<?php endforeach; ?>
		</div>
	</div>
<?php
// Large list, render it using React
else:
	if ( ! is_array($value))
	{
		$label = isset($choices[$value]) ? $choices[$value] : $value;
		$value = [$value => $label];
	}
	$component = [
		'name' => $field_name,
		'items' => $choices,
		'selected' => $value,
		'multi' => $multi,
		'too_many' => $too_many,
		'filter_url' => $filter_url,
		'limit' => $limit
	];
	if (isset($no_results['text'])) {
		$component['no_results'] = lang($no_results['text']);
	}
	?>
	<div data-select-react="<?=base64_encode(json_encode($component))?>">
		<div class="fields-select">
			<div class="field-inputs">
				<label class="field-loading">
					Loading<span></span>
				</label>
			</div>
		</div>
	</div>
<?php endif ?>
