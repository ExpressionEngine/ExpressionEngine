<!doctype html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<meta name="modal-title" content="<?=isset($modal_title) ? $modal_title : ''?>">
		<?=ee()->view->head_link('css/common.min.css'); ?>
		<?php if (ee()->extensions->active_hook('cp_css_end') === TRUE):?>
		<link rel="stylesheet" href="<?=ee('CP/URL', 'css/cp_global_ext')?>" type="text/css" />
		<?php endif;?>
		<?php
		foreach (ee()->cp->get_head() as $item)
		{
			echo $item."\n";
		}
		?>
	</head>
	<body class="app-modal-inner">
		<div class="app-modal__dismiss">
			<a class="js-modal-close" rel="modal-form" href="#"><?=lang('close_modal')?></a> <span class="txt-fade">[esc]</span>
		</div>

		<?=$child_view?>

		<div class="overlay"></div>
		<div class="app-overlay"></div>

		<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>
		<?php echo implode('', ee('CP/Modal')->getAllModals()); ?>

		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->view->script_tag('common.js')?>
		<?=ee()->javascript->get_global()?>
		<?=ee()->cp->render_footer_js()?>

		<?php if (isset($_extra_library_src))
		{
			echo $_extra_library_src;
		} ?>

		<?=ee()->javascript->script_foot()?>

		<?php foreach (ee()->cp->get_foot() as $item)
		{
			echo $item."\n";
		} ?>
	</body>
</html>
