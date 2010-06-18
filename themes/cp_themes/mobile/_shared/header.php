<!doctype html>
<html>
  <head>
    <meta charset="UTF-8" />

	<title><?=$cp_page_title?> | ExpressionEngine</title>

	<link rel="stylesheet" href="<?=base_url()?>themes/jquery_ui/default/jquery-ui-1.7.2.custom.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?=BASE.AMP.'C=css'?>" type="text/css" media="screen" title="Global Styles" charset="utf-8" />	
	<?php

	if (isset($cp_global_js))
	{
		echo $cp_global_js;
	}
	?>
	<script type="text/javascript" src="<?=$cp_theme_url?>javascript/jquery.1.3.2.js"></script>
	<script type="text/javascript" src="<?=$cp_theme_url?>javascript/jqtouch.js"></script>
	<script type="text/javascript" src="<?=$cp_theme_url?>javascript/jqtouch.transitions.js"></script>
	<script type="text/javascript" src="<?=$cp_theme_url?>javascript/ee_mobile_js.js"></script>
	<?php
	if (isset($script_head))
	{
		echo $script_head;
	}
	
	foreach ($this->cp->its_all_in_your_head as $item)
	{
		echo $item."\n";
	}
	?>


</head>
<body>
<?php
/* End of file header.php */
/* Location: ./themes/cp_themes/mobile/_shared/header.php */