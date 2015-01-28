<!doctype html>
<html>
	<head>
		<title><?=$title?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<link href="<?=$theme_url?>/ee/cp_themes/default/css/v3/common.min.css" rel="stylesheet">
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
			echo '<meta http-equiv="refresh" content="5;url='.$refresh_url.'" />';
		}
		?>
	</head>
	<body id="top">
		<section class="wrap">
			<div class="col-group">
				<div class="col w-16 last">
					<div class="box">
						<h1><?=$title?> <span class="required intitle">Completed Step 1 of 4</span></h1>
						<form class="settings" action="<?=$action?>" method="<?=$method?>">
							<?=$content?>
						</form>
					</div>
				</div>
			</div>
		</section>
		<section class="product-bar">
			<div class="snap">
				<div class="left">
					<p><b>ExpressionEngine<?php if ($is_core): echo ' '.$is_core; endif;?></b> <span title="About ExpressionEngine"><b><?=$version_major?></b>.<?=$version_minor?></span></p>
				</div>
				<div class="right"><p><a href="/report-bug" rel="external">Report Bug</a> <b class="sep">&middot;</b> <a href="/new-ticket" rel="external">New Ticket</a> <b class="sep">&middot;</b> <a href="/manual" rel="external">Manual</a></p></div>
			</div>
		</section>
		<section class="footer">
			<div class="snap">
				<div class="left"><p>&copy;2003&mdash;<?=date('Y')?> <a href="http://ellislab.com/expressionengine" rel="external">EllisLab</a>, Inc.</p></div>
				<div class="right"><p><a class="scroll" href="#top">scroll to top</a></p></div>
			</div>
		</section>
		<!--
		<script type="text/javascript" src="<?=$javascript_path?>jquery/jquery.js"></script>
		-->
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" type="text/javascript"></script>
		<script src="<?=$theme_url?>/ee/javascript/src/v3/common.min.js" type="text/javascript"></script>
	</body>
</html>
