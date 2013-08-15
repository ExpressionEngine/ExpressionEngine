<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title><?=$title?></title>
<script type="text/javascript" src="<?=$javascript_path?>jquery/jquery.js"></script>

<style type="text/css">

	<?php $this->load->view('css'); ?>

</style>

<?php

if (isset($extra_header))
{
	echo $extra_header;
}

if (isset($refresh) && $refresh === TRUE)
{
	if ($this->input->get('ajax_progress') == 'yes')
	{
		$refresh_url .= '&ajax_progress=yes';
	}
	echo '<meta http-equiv="refresh" content="5;url='.$refresh_url.'" />';
}
?>

</head>
<body>

	<div id="outer">

		<div id="header">

			<a href="<?=SELF?>"><img src="<?=$image_path?>logo.gif" width="241" height="88" border="0" alt="ExpressionEngine Installation Wizard" /></a>

		</div>

		<div id="inner">

			<h1><?=$heading?></h1>

			<div id="content">

				<?=$content?>

			</div>

			<div id="footer">

				ExpressionEngine <?=$is_core.$version?> - &#169; <?=$copyright?>

			</div>

		</div>

	</div>

</body>
</html>
