<!doctype html>
<html>
	<head>
		<?=ee()->view->head_title($cp_page_title)?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<?php if (isset($meta_refresh)): ?>
		<meta http-equiv='refresh' content='<?=$meta_refresh['rate']?>; url=<?=$meta_refresh['url']?>'>
		<?php endif;?>

		<?=ee()->view->head_link('css/common.min.css'); ?>
		<?php if (ee()->extensions->active_hook('cp_css_end') === TRUE):?>
		<link rel="stylesheet" href="<?=ee('CP/URL', 'css/cp_global_ext')?>" type="text/css" />
		<?php endif;?>

		<?php
		foreach (ee()->cp->get_head() as $item) {
			echo $item."\n";
		}
		?>
	</head>
	<body id="top">
		<div class="global-alerts">
		<?=ee('CP/Alert')->getAllBanners()?>
		</div>

<?php
