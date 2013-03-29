<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">

	<?=$this->view->head_title($cp_page_title)?>
	<?=$this->view->head_link('css/jquery-ui-1.8.16.custom.css'); ?>
	<?=$this->view->head_link('css/global.css'); ?>
	<?=$this->view->head_link('css/override.css'); ?>

	<?php if ($this->extensions->active_hook('cp_css_end') === TRUE):?>
	<link rel="stylesheet" href="<?=BASE.AMP.'C=css'.AMP.'M=cp_global_ext'.AMP.'theme='.$this->cp->cp_theme;?>" type="text/css" />
	<?php endif;?>
	<!--[if lte IE 7]><?=$this->view->head_link('css/iefix.css')?><![endif]-->


	<?php 
	if (isset($cp_global_js))
	{
		echo $cp_global_js;
	} ?>

	<?=$this->view->script_tag('jquery/jquery.js')?>
	

	<?php
	foreach ($this->cp->its_all_in_your_head as $item)
	{
		echo $item."\n";
	}
	?>
	
</head>
<body>
<noscript>
<div class="js_notification" style="top: 0;">
	<div class="notice_inner js_error">
		<span><?=lang('no_js_warning')?></span>
	</div>
</div>
</noscript>
<!--[if lte IE 6]>
<div class="js_notification" style="top: 0;">
	<div class="notice_inner js_error">
		<span><?=lang('ie_6_warning')?></span>
	</div>
</div>
<![endif]-->

<div id="branding"></div>

<?php
/* End of file header.php */
/* Location: ./themes/cp_themes/default/_shared/header.php */