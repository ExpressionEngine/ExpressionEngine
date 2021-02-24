<?php
ee()->load->helper('text');

$date_format = ee()->session->userdata('date_format', ee()->config->item('date_format'));
$build = ee()->localize->format_date(
    $date_format,
    ee()->localize->parse_build_date($build),
    true
);
?>
<b><?=formatted_version($version)?></b><em><?=lang('build')?> <?=$build?></em>
