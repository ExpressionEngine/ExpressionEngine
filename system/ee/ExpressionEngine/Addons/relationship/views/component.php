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
    'button_label' => isset($button_label) ? $button_label : null,
    'select_filters' => $select_filters,
    'can_add_items' => (REQ != 'CP') ? false : !$in_modal,
    'channels' => $channels,
    'display_entry_id' => $display_entry_id,
    'display_status' => $display_status,
    'rel_min' => $rel_min,
    'rel_max' => $rel_max,
];

echo base64_encode(json_encode($component));