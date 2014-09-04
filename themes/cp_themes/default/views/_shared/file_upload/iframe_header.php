<!DOCTYPE html>
<html lang="en" id="file_upload_iframe">
<head>
	<meta charset="utf-8">

	<?=$this->view->head_title($cp_page_title)?>
	<?=$this->view->head_link('css/jquery-ui-1.8.16.custom.css'); ?>
	<?=$this->view->head_link('css/global.css'); ?>
	<?=$this->view->head_link('css/file_browser.css'); ?>
	<?=$this->view->head_link('css/override.css'); ?>

	<?php if ($this->extensions->active_hook('cp_css_end') === TRUE):?>
	<link rel="stylesheet" href="<?=BASE.AMP.'C=css'.AMP.'M=cp_global_ext';?>" type="text/css" />
	<?php endif;?>
	<!--[if lte IE 7]><?=$this->view->head_link('css/iefix.css')?><![endif]-->

	<?php 
	if (isset($cp_global_js))
	{
		echo $cp_global_js;
	} ?>
	
	<?=$this->view->script_tag('jquery/jquery.js')?>

	<?php
	if (isset($script_head)) 
	{
		echo $script_head;
	}

	foreach ($this->cp->its_all_in_your_head as $item)
	{
		echo $item."\n";
	}
	?>
</head>
<body id="mainContent" class="pageContents">