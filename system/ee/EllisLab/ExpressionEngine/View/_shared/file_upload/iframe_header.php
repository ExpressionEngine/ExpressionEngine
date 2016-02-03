<!DOCTYPE html>
<html lang="en" id="file_upload_iframe">
<head>
	<meta charset="utf-8">

	<?=ee()->view->head_title($cp_page_title)?>
	<?=ee()->view->head_link('css/global.css'); ?>
	<?=ee()->view->head_link('css/file_browser.css'); ?>
	<?=ee()->view->head_link('css/override.css'); ?>

	<?php if (ee()->extensions->active_hook('cp_css_end') === TRUE):?>
	<link rel="stylesheet" href="<?=BASE.AMP.'C=css'.AMP.'M=cp_global_ext';?>" type="text/css" />
	<?php endif;?>
	<!--[if lte IE 7]><?=ee()->view->head_link('css/iefix.css')?><![endif]-->

	<?php
	if (isset($cp_global_js))
	{
		echo $cp_global_js;
	} ?>

	<?=ee()->view->script_tag('jquery/jquery.js')?>

	<?php
	if (isset($script_head))
	{
		echo $script_head;
	}

	foreach (ee()->cp->its_all_in_your_head as $item)
	{
		echo $item."\n";
	}
	?>
</head>
<body id="mainContent" class="pageContents">
