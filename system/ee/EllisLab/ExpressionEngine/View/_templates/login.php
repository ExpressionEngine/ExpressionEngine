<!doctype html>
<html>
	<head>
		<?=ee()->view->head_title($cp_page_title)?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<?=ee()->view->head_link('css/out.min.css'); ?>
	</head>
	<body>
		<section class="flex-wrap">
			<section class="wrap">

				<?=$child_view?>

				<section class="bar">
					<p class="left"><a href="https://expressionengine.com/" rel="external"><b>ExpressionEngine</b></a></p>
					<p class="right">&copy;<?=ee()->localize->format_date('%Y')?> <a href="https://ellislab.com/" rel="external">EllisLab</a> Corp.</p>
				</section>

			</section>
		</section>
		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->view->script_tag('common.min.js')?>
		<?=ee()->view->script_tag('cp/login.js')?>
		<script type="text/javascript">
			$(document).ready(function()
			{
				document.getElementById('<?=$focus_field?>').focus();
			});
		</script>
	</body>
</html>
