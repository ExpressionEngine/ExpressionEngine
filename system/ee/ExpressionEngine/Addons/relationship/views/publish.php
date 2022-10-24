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
    'channels' => $channels,
    'display_entry_id' => $display_entry_id,
    'rel_min' => $rel_min,
    'rel_max' => $rel_max,
];

$placeholder = '<label class="field-loading">' . lang('loading') . '<span></span></label>';

if ($deferred) {
    echo '<div class="react-deferred-loading--relationship">';

    $template = '<li class="list-item">
            <div class="list-item__content">
                <div class="list-item__title">%s</div>
                <div class="list-item__secondary">%s</div>
            </div>
            <input type="hidden" name="%s" value="%d">
        </li>';

    $items = [];
    $placeholder = '';

    foreach ($selected as $relatedEntry) {
        $items[] = sprintf(
            $template,
            $relatedEntry['label'],
            $relatedEntry['instructions'],
            $field_name . ($multi ? '[]' : ''),
            $relatedEntry['value']
        );
    }

    if (!empty($items)) {
        $placeholder .= '<ul class="list-group list-group--connected mb-s ui-sortable">' . implode("\n", $items) . '</ul>';
    }

    $placeholder .= '
        <button type="button" class="js-dropdown-toggle button button--default">
            <i class="fas fa-edit icon-left"></i>' . lang('relate_entry_deferred') . '
        </button>
    ';
}
?>
<div data-relationship-react="<?=base64_encode(json_encode($component))?>" data-input-value="<?=$field_name?>" <?php echo ($deferred ? 'class="react-deferred-loading"' : '') ?>>
    <div class="fields-select">
        <div class="field-inputs">
            <?php echo $placeholder ?>
        </div>
    </div>
</div>
<?php
if ($deferred) {
    echo '</div>';
}
?>
