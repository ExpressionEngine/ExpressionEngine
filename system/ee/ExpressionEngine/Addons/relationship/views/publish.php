<?php

if (empty($choices)) {
    return $this->embed('ee:_shared/form/no_results', $no_results);
}
if (!isset($display_status)) {
    $display_status = false;
}
$component = [
    'items' => $choices,
    'selected' => $selected,
    'multi' => $multi,
    'filter_url' => $filter_url,
    'limit' => $limit,
    'no_results' => strip_tags(lang($no_results['text'])),
    'no_related' => lang($no_related['text']),
    'new_entry' => isset($lang) && isset($lang['new_entry']) ? $lang['new_entry'] : lang('new_entry'),
    'button_label' => isset($button_label) ? $button_label : null,
    'select_filters' => $select_filters,
    'can_add_items' => (REQ != 'CP') ? false : (!$in_modal && $canCreateNew),
    'can_edit_items' => (REQ != 'CP') ? false : !$in_modal,
    'channels' => $channels,
    'channelsForNewEntries' => isset($channelsForNewEntries) ? $channelsForNewEntries : [],
    'display_entry_id' => $display_entry_id,
    'display_status' => $display_status,
    'rel_min' => $rel_min,
    'rel_max' => $rel_max,
];

if (isset($publishCreateUrl)) {
    $component['publishCreateUrl'] = $publishCreateUrl;
}
if (isset($publishEditUrl)) {
    $component['publishEditUrl'] = $publishEditUrl;
}
if (isset($showCreateDropdown)) {
    $component['showCreateDropdown'] = $showCreateDropdown && !empty($channelsForNewEntries);
}
if (isset($lang)) {
    $component['lang'] = $lang;
}

$placeholder = '<label class="field-loading">' . lang('loading') . '<span></span></label>';

if ($deferred) {
    echo '<div class="react-deferred-loading--relationship">';

    $instructionsTemplate = '<div class="list-item__secondary">';
    if ($display_entry_id) {
        $instructionsTemplate .= '<span> #%d / </span>';
    }
    $instructionsTemplate .= '%s';
    if ($display_status) {
        $instructionsTemplate .= '<span class="status-indicator" style="border-color: #%s; color: #%s;">%s</span>';
    }
    $instructionsTemplate .= '</div>';
    $template = '<li class="list-item">
            <div class="list-item__content">
                <div class="list-item__title">%s</div>
                %s
            </div>
            <input type="hidden" name="%s" value="%d">
        </li>';

    $items = [];
    $placeholder = '';

    foreach ($selected as $relatedEntry) {
        $instructionParams = [];
        if ($display_entry_id) {
            $instructionParams[] = $relatedEntry['value'];
        }
        $instructionParams[] = $relatedEntry['instructions'];
        if ($display_status) {
            $instructionParams[] = array_key_exists($relatedEntry['status'], $statuses) ? $statuses[$relatedEntry['status']] : '';
            $instructionParams[] = array_key_exists($relatedEntry['status'], $statuses) ? $statuses[$relatedEntry['status']] : '';
            $instructionParams[] = $relatedEntry['status'];
        }
        $instructionsRow = vsprintf(
            $instructionsTemplate,
            $instructionParams
        );
        $items[] = sprintf(
            $template,
            $relatedEntry['label'],
            $instructionsRow,
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
