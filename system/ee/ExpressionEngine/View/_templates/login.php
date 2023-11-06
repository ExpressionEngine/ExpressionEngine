<!doctype html>
<html lang="<?=ee()->lang->code()?>" dir="ltr">
	<head>
		<?=ee()->view->head_title($cp_page_title)?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
		<?php if (ee('pro:Access')->hasRequiredLicense() && ee()->config->item('favicon')) : ?>
		<link rel="icon" type="image/x-icon" href="<?=ee()->config->item('favicon')?>" />
		<?php endif; ?>
		<?=ee()->view->head_link('css/common.min.css'); ?>
	</head>
	<body data-ee-version="<?=APP_VER?>">
		<script type="text/javascript">
			var currentTheme = localStorage.getItem('theme');

			// Restore the currently selected theme
			// This is at the top of the body to prevent the default theme from flashing
			if (currentTheme) {
				document.body.dataset.theme = currentTheme;
			}
		</script>
		<section role="main" class="login-container">
			<section class="login">

				<?=$child_view?>

			</section>
		</section>
		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->view->script_tag('common.min.js')?>
		<?=ee()->view->script_tag('vendor/focus-visible.js')?>

		<script type="text/javascript">
			var EE = {
				PATH_CP_GBL_IMG: "<?=PATH_CP_GBL_IMG?>",
				cp: {
					validatePasswordUrl: "<?=ee('CP/URL', 'login/validate_password')->compile()?>"
				},
				lang: {
					password_icon: "<?=lang('password_icon')?>"
				}
			}
			$(document).ready(function()
			{
				document.getElementById('<?=$focus_field?>').focus();
			});
		</script>
		<?=ee()->view->script_tag('cp/login.js')?>
		<?=ee()->view->script_tag('cp/passwords.js')?>
	</body>
</html>
