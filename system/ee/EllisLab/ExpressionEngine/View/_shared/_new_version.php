<?php
ee()->load->helper('text');

$date_format = ee()->session->userdata('date_format', ee()->config->item('date_format'));
$build = ee()->localize->format_date(
	$date_format,
	ee()->localize->parse_build_date($build),
	TRUE
);
?>
<div class="app-about-info__latest">
	<h3><?=lang('latest_version')?></h3>
	<?=lang('version')?>: <?=formatted_version($version)?><br>
	<em><?=lang('build')?> <?=$build?></em>
</div>
