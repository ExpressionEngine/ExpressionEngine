<!doctype html>
<html>
	<head>
		<title><?=$title?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<link href="<?=$theme_url?>/ee/cp/css/common.min.css" rel="stylesheet">
		<?php
		if (isset($extra_header))
		{
			echo $extra_header;
		}

		if (isset($refresh) && $refresh === TRUE)
		{
			if ($ajax_progress)
			{
				$refresh_url .= '&ajax_progress=yes';
			}
			echo '<meta http-equiv="refresh" content="1;url='.$refresh_url.'" />';
		}
		?>
	</head>
	<body id="top">
		<section class="wrap">
			<div class="col-group install-wrap">
				<div class="col w-16 last">
					<div class="box">
						<h1><?=($header) ?: $title?> <span class="req-title<?php if (stripos($action, 'do_install') == FALSE): ?> no-asterisk<?php endif; ?>"><?=$subtitle?></span></h1>
						<form class="settings" action="<?=$action?>" method="<?=$method?>">
							<?=$content?>
						</form>
					</div>
				</div>
			</div>
			<section class="product-bar">
				<div class="snap">
					<div class="left">
						<p><b>ExpressionEngine<?php if ($is_core): echo ' '.$is_core; endif;?></b> <span title="About ExpressionEngine"><b><?=$version_major?></b>.<?=$version_minor?></span></p>
					</div>
					<div class="right"><p><a href="https://expressionengine.com/support/bugs/new" rel="external">Report Bug</a> <b class="sep">&middot;</b> <a href="https://expressionengine.com/support/ticket/new" rel="external">New Ticket</a> <b class="sep">&middot;</b> <a href="https://docs.expressionengine.com/v3/" rel="external">Manual</a></p></div>
				</div>
			</section>
			<section class="footer">
				<div class="snap">
					<div class="left"><p>&copy;2003&mdash;<?=date('Y')?> <a href="https://ellislab.com/" rel="external">EllisLab</a>, Inc.</p></div>
					<div class="right"><p><a class="scroll" href="#top">scroll to top</a></p></div>
				</div>
			</section>
		</section>

		<script src="<?=$javascript_path?>jquery/jquery.js" type="text/javascript"></script>
		<script src="<?=$javascript_path?>common.js" type="text/javascript"></script>
	</body>
</html>
