<?php

if (empty($choices)) {
	return $this->embed('ee:_shared/form/no_results', $no_results);
}
$component = [
	'name' => $field_name,
	'items' => $choices,
	'selected' => $selected,
	'multi' => $multi,
	'filter_url' => $filter_url,
	'limit' => $limit
];
if (isset($no_results['text'])) {
	$component['no_results'] = lang($no_results['text']);
}
?>
<div data-relationship-react="<?=base64_encode(json_encode($component))?>">
	<div class="fields-select">
		<div class="field-inputs">
			<label class="field-loading">
				Loading<span></span>
			</label>
		</div>
	</div>
</div>
