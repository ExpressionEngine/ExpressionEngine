<?php
$too_many = isset($too_many) ? $too_many : 8;
$empty_text = isset($empty_text) ? $empty_text : lang('choose_wisely');
$field_disabled = isset($field_disabled) ? $field_disabled : false;
$class = isset($class) ? $class : '';

$react = '';
$sub_class = 'fields-select-drop';
$display_text = $empty_text;

if ($field_disabled) {
    $sub_class .= ' field-disabled';
    $display_text = isset($choices[$value]) ? $choices[$value] : $empty_text;
} else {
    $component = [
        'name' => $field_name,
        'items' => ee('View/Helpers')->normalizedChoices($choices),
        'selected' => $value,
        'disabled' => isset($disabled) ? $disabled : false,
        'tooMany' => $too_many,
        'filterUrl' => isset($filter_url) ? $filter_url : null,
        'limit' => isset($limit) ? $limit : 100,
        'groupToggle' => isset($group_toggle) ? $group_toggle : null,
        'emptyText' => $empty_text,
        'noResults' => isset($no_results['text']) ? lang($no_results['text']) : null,
        'conditionalRule' => isset($conditional_toggle) ? $conditional_toggle : null,
        'isRequired' => isset($is_required) ? isset($is_required) : false,
        'fileManager' => isset($fileManager) ? $fileManager : false,
        'ignoreSectionLabel' => isset($ignoreSectionLabel) ? $ignoreSectionLabel : false,
        'disabledInput' => false
    ];
    
    $react = 'data-dropdown-react="' . base64_encode(json_encode($component)) . '" data-input-value="' . $field_name . '"';
}

?>
<div <?=$react?> class="<?=$class?>">
	<div class="<?=$sub_class?>">
		<div class="select">
			<div class="select__button">
				<label class="select__button-label">
				<?=$display_text?>
				</label>
			</div>
		</div>
	</div>
</div>
