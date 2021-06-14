<!doctype html>
<html lang="<?=ee()->lang->code()?>" dir="ltr">
	<head>
		<?=ee()->view->head_title($cp_page_title)?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
		<?php if (IS_PRO && ee('pro:Access')->hasValidLicense() && ee()->config->item('favicon')) : ?>
		<link rel="icon" type="image/x-icon" href="<?=ee()->config->item('favicon')?>" />
		<?php endif; ?>
		<?=ee()->view->head_link('css/common.min.css'); ?>
	</head>
	<body data-ee-version="<?=APP_VER?>">
		<section role="main" class="login-container">
			<section class="login">

				<?=$child_view?>

			</section>
		</section>
		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->view->script_tag('common.min.js')?>
		<?=ee()->view->script_tag('vendor/focus-visible.js')?>
		<?=ee()->view->script_tag('cp/login.js')?>
		<script type="text/javascript">
			$(document).ready(function()
			{
				document.getElementById('<?=$focus_field?>').focus();
			});
		</script>
	</body>
</html>
