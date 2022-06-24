<!doctype html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<?php if (ee('pro:Access')->hasRequiredLicense() && ee()->config->item('favicon')) : ?>
		<link rel="icon" type="image/x-icon" href="<?=ee()->config->item('favicon')?>" />
		<?php endif; ?>
		<?php if (isset($meta_refresh)): ?>
		<meta http-equiv='refresh' content='<?=$meta_refresh['rate']?>; url=<?=$meta_refresh['url']?>'>
		<?php endif;?>

		<?=ee()->view->head_link('css/common.min.css'); ?>

		<?php if (ee()->extensions->active_hook('cp_css_end') === true):?>
		<link rel="stylesheet" href="<?=ee('CP/URL', 'css/cp_global_ext')?>" type="text/css" />
		<?php endif;?>
		<!-- <link href="touch-icon-iphone.png" rel="apple-touch-icon-precomposed" sizes="114x114">
		<link href="touch-icon-ipad.png" rel="apple-touch-icon-precomposed" sizes="144x144"> -->

		<?php
        foreach (ee()->cp->get_head() as $item) {
            echo $item . "\n";
        }
        ?>
	</head>
	<body class="iframe">
		<div class="box" style="margin: 0 20px;"><?=$content?></div>
		<?=ee()->javascript->inline('var EE = window.parent.EE;');?>
		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->cp->render_footer_js()?>
		<script type="text/javascript">
			$('input.btn').on('click', function(event) {
				$(this).attr('value', $(this).data('work-text')).addClass('work');
			});
		</script>
	</body>
</html>
