<?php

$this->table->clear();
$cp_table_template['table_open'] = '<div class="nav_history_table-wrap"><table width="100%" data-skipped="' . lang('skipped') . '" class="structure-table nav_history_table" cellspacing="0" cellpadding="0">';

$this->table->set_template($cp_table_template);
$this->table->set_heading(array(
    lang('note'),
    lang('date'),
    lang('structure_version'),
    lang('status'),
    lang('restore'),
));

if (!count($structure_nav_history)) {
    $this->table->add_row(array(
        lang('no_history_entries'), "", "", ""
    ));
}

foreach ($structure_nav_history as $structure_nav) {
    $row_status = '--';
    $row_status_class = '';

    // if it's the current nav.. lets not give the restore option
    if ($structure_nav->current) {
        $row_status = 'Current';
        $link = '<a href="#" onclick="return false;" class="structure_btn disable">CURRENT</a>';
    } else {
        $link = '<a class="structure_btn action" href="' . $base_url . AMP . 'method=restore' . AMP . 'id=' . $structure_nav->id . '">' . lang("restore") . '</a>';
    }

    if ($structure_nav->restored_date > 0) {
        $row_status = 'Restored ' . date('Y-m-d g:ia', strtotime($structure_nav->restored_date));
        $row_status_class = ' restored';
    }

    $this->table->add_row(array(
        $structure_nav->note,
        $structure_nav->date,
        $structure_nav->structure_version,
        array('data' => $row_status, 'class' => 'status_col' . $row_status_class),
        array('data' => $link, 'class' => ($structure_nav->current ? 'current' : '')),
    ));
}

echo $this->table->generate();

echo '<div class="pagination">', $pagination, '</div>';
