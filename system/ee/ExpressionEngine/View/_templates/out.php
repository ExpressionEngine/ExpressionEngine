<!doctype html>
<html lang="<?=ee()->lang->code()?>" dir="ltr">
	<head>
		<title><?=$cp_page_title?> | ExpressionEngine</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<?php if (ee('pro:Access')->hasRequiredLicense() && ee()->config->item('favicon')) : ?>
		<link rel="icon" type="image/x-icon" href="<?=ee()->config->item('favicon')?>" />
		<?php endif; ?>
		<meta name="referrer" content="no-referrer">
		<meta name="robots" content="noindex, nofollow">
		<?=ee()->view->head_link('css/common.min.css')?>
	</head>
	<body data-ee-version="<?=APP_VER?>" class="installer-page">
		<section class="flex-wrap">
			<section class="wrap">

				<?=$child_view?>

				<?php if (! isset($branded) or $branded !== false):?>
					<section class="bar">
						<p style="float: left;"><a href="https://expressionengine.com/" rel="external"><b>ExpressionEngine</b></a></p>
						<p style="float: right;">&copy;<?=date('Y')?> <a href="https://packettide.com/" rel="external">Packet Tide</a>, LLC</p>
					</section>
				<?php endif; ?>
			</section>
		</section>
	</body>
</html>
