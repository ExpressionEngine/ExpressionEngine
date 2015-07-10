<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title><?=$cp_page_title?> | <?=APP_NAME?></title>

	<link rel="stylesheet" href="<?=BASE.AMP.'C=css'?>" type="text/css" media="screen" />

	<?php

	if (isset($cp_global_js))
	{
		echo $cp_global_js;
	}

	if (isset($library_src))
	{
		echo $library_src;
	}

	if (isset($script_head))
	{
		echo $script_head;
	}

	foreach ($this->cp->its_all_in_your_head as $item)
	{
		echo $item."\n";
	}
	?>

	<script type="text/javascript" src="<?=BASE.AMP.'C=javascript'?>"></script>
</head>

<body onload="<?=$cp_page_onload?>">

