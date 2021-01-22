<?php

if (empty($choices)) {
    return $this->embed('ee:_shared/form/no_results', $no_results);
}
$component = [
    'items' => $choices,
    'selected' => $selected,
    'multi' => $multi,
    'filter_url' => $filter_url,
    'limit' => $limit,
    'no_results' => lang($no_results['text']),
    'no_related' => lang($no_related['text']),
    'button_label' => isset($button_label) ? $button_label : null,
    'select_filters' => $select_filters,
    'can_add_items' => (REQ != 'CP') ? false : !$in_modal,
    'channels' => $channels
];
?>
<div data-relationship-react="<?=base64_encode(json_encode($component))?>" data-input-value="<?=$field_name?>">
	<div class="fields-select">
		<div class="field-inputs">
			<label class="field-loading">
				<?=lang('loading')?><span></span>
			</label>
		</div>
	</div>
</div>
