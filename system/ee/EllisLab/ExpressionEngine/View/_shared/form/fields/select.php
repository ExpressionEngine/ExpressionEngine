<?php
$too_many = 8;

if (count($choices) == 0) return;

// If it's a small list, just render it server-side
if (count($choices, COUNT_RECURSIVE) <= $too_many):
	// For radios with no value, set value to first choice
	if ( ! $multi && ! $value) {
		$keys = array_keys($choices);
		$value = $keys[0];
	}
	?>
	<div class="fields-select">
		<div class="field-inputs">
			<?php foreach ($choices as $key => $choice): ?>
				<?php $this->embed('ee:_shared/form/fields/select-item', [
					'field_name' => $field_name,
					'key' => $key,
					'attrs' => isset($attrs) ? $atts : '',
					'choice' => $choice,
					'value' => $value
				]); ?>
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

	$nested = isset($nested) ? $nested : FALSE;

	// Normalize choices into an array to keep order of items, order cannot be
	// counted on in a JavaScript object
	$normalized_choices = [];
	if ($nested)
	{
		$normalized_choices = arrayForChoices($choices, TRUE);
	}
	else {
		foreach ($choices as $key => $choice)
		{
			// Allow for one-level of items below group headings for now
			if ( ! $nested && is_array($choice) && is_string($key))
			{
				$normalized_choices[] = ['section' => $key];
				foreach ($choice as $key => $child)
				{
					$normalized_choices[] = [
						'value' => $key,
						'label' => $child
					];
				}
			}
		}
	}
	$component = [
		'name' => $field_name,
		'items' => $normalized_choices,
		'selected' => $value,
		'multi' => $multi,
		'nested' => $nested,
		'too_many' => $too_many,
		'filter_url' => $filter_url,
		'limit' => $limit,
		'toggle_all' => NULL
	];
	if (isset($no_results['text']))
	{
		$component['no_results'] = lang($no_results['text']);
	}
	?>
	<div data-select-react="<?=base64_encode(json_encode($component))?>">
		<div class="fields-select">
			<div class="field-inputs">
				<label class="field-loading">
					<?=lang('loading')?><span></span>
				</label>
			</div>
		</div>
	</div>
<?php endif ?>
<?php
if ( ! function_exists('arrayForChoices'))
{
	function arrayForChoices($choices)
	{
		$return_array = [];
		foreach ($choices as $value => $label)
		{
			$choice = [
				'value' => $value,
				'label' => $label
			];

			if (is_array($label))
			{
				$choice['label'] = $label['name'];
				$choice['children'] = arrayForChoices($label['children']);
			}

			$return_array[] = $choice;
		}

		return $return_array;
	}
}
?>
