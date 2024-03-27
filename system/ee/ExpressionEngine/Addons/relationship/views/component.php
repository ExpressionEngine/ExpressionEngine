<?php
// React component for the Relationship fieldtype
$component = [
    'items' => $choices,
    'selected' => $selected,
    'multi' => $multi,
    'filter_url' => $filter_url,
    'limit' => $limit,
    'no_results' => lang($no_results['text']),
    'no_related' => lang($no_related['text']),
    'new_entry' => isset($lang) && isset($lang['new_entry']) ? $lang['new_entry'] : lang('new_entry'),
    'button_label' => isset($button_label) ? $button_label : null,
    'select_filters' => $select_filters,
    'can_add_items' => (REQ != 'CP') ? false : !$in_modal,
    'can_edit_items' => (REQ != 'CP') ? false : !$in_modal,
    'channels' => $channels,
    'channelsForNewEntries' => isset($channelsForNewEntries) ? $channelsForNewEntries : [],
    'display_entry_id' => $display_entry_id,
    'display_status' => isset($display_status) && $display_status === true,
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

echo base64_encode(json_encode($component));