<!doctype html>
<html>
	<head>
		<?=ee()->view->head_title($cp_page_title)?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<?=ee()->view->head_link('css/out.min.css'); ?>
	</head>
	<body>
		<section class="wrap">

			<?=$child_view?>

		</section>
		<section class="bar snap">
			<p class="left"><b>ExpressionEngine</b></p>
			<p class="right">&copy;2003&mdash;<?=ee()->localize->format_date('%Y')?> <a href="https://ellislab.com/expressionengine" rel="external">EllisLab</a>, Inc.</p>
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
