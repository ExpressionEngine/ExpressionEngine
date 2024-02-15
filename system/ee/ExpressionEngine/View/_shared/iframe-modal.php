<!doctype html>
<html lang="<?=ee()->lang->code()?>" dir="ltr">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<meta name="modal-title" content="<?=isset($modal_title) ? $modal_title : ''?>">
		<?=ee()->view->head_link('css/common.min.css'); ?>
		<?php if (ee()->extensions->active_hook('cp_css_end') === true):?>
		<link rel="stylesheet" href="<?=ee('CP/URL', 'css/cp_global_ext')?>" type="text/css" />
		<?php endif;?>
		<?php
        foreach (ee()->cp->get_head() as $item) {
            echo $item . "\n";
        }
        ?>
	</head>
	<body data-ee-version="<?=APP_VER?>" class="<?php if (isset($pro_class)) echo $pro_class; ?> app-modal-inner">
		<?php if (!isset($pro_class)): ?>
		<script type="text/javascript">
		var currentTheme = localStorage.getItem('theme');

		// Restore the currently selected theme
		// This is at the top of the body to prevent the default theme from flashing
		if (currentTheme) {
			document.body.dataset.theme = currentTheme;
		}
		</script>
		<?php endif; ?>
		<?php if (!isset($hide_topbar) || !$hide_topbar) : ?>
		<div class="app-modal__dismiss">
			<a class="js-modal-close" rel="modal-form" href="#"><?=lang('close_modal')?></a> <span class="txt-fade">[esc]</span>
		</div>
		<?php endif; ?>

		<?php if (isset($left_nav)): ?>
			<div class="secondary-sidebar-container">
				<?=$left_nav?>

				<div class="container" style="position: relative;">
					<?=$child_view?>
				</div>
			</div>
		<?php else : ?>

				<?=$child_view?>
		<?php endif; ?>

		<div class="overlay"></div>
		<div class="app-overlay"></div>

		<?php 
		if (isset($blocks['modals'])) {
			echo $blocks['modals'];
		}
		echo implode('', ee('CP/Modal')->getAllModals()); 
		?>

		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->javascript->get_global()?>
		<?=ee()->cp->render_footer_js()?>

		<?php if (isset($_extra_library_src)) {
            echo $_extra_library_src;
        } ?>

		<?=ee()->javascript->script_foot()?>

		<?php foreach (ee()->cp->get_foot() as $item) {
            echo $item . "\n";
        } ?>
	</body>
</html>
