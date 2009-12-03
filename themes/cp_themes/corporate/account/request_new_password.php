<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title><?=$cp_page_title?> | ExpressionEngine</title>
<style type="text/css">

body {
	background-color:	#27343C;
	font-family:		"Helvetica Neue", Arial, Helvetica, Geneva, sans-serif;
	text-align:			center;
	font-size:			12px;
	color:				#ecf1f3;
	margin:				0;
	padding:			0;
}

#content  {
	text-align:			left;
	padding:			160px 20px 5px 20px;
	margin:				20px auto 0 auto;
	width:				440px;
	background:			url('<?=$cp_theme_url?>images/ee_login_bg.gif') no-repeat center top;
}

h1 {
	font-weight:		normal;
	font-size:			14px;
	color:				#ecf1f3;
	margin: 			0 0 4px 0;
}

#branding {
	background:			#27343c url('<?=$cp_theme_url?>images/branding_bg.gif') top left repeat-x;
	text-align:			right;
	padding-right:		125px;
}

#branding img {
	border:				0;
}

a:link, a:visited {
	color:				#8fb11f;
	text-decoration:	underline;
}

a:hover {
	color:				#ffffff;
	text-decoration:	underline;
}

dt {
	font-size:			16px;
	line-height:		24px;
	margin-bottom:		5px;
}

dd {
	margin:				0 0 15px 0;
	font-size:			11px;
	line-height:		24px;
	color:				#666;
}

input, .error, .success {
	font-family:		 "Trebuchet MS", sans-serif;
	font-size:			1.2em;
	border:				none;
	-webkit-border-radius: 16px;
	-moz-border-radius:	16px;
	padding:			10px;
	width:				430px;
	margin-right:		5px;
}


input.submit {
	background:			#8fb11f url('<?=$cp_theme_url?>images/btn_grad.png') top left repeat-x;
	padding:			1px 35px 2px 35px;
	color:				#fff;
	letter-spacing: 	0.6px;
	text-transform:		uppercase;
	font-weight:		bold;
	text-shadow: 		#7fa41b -1px -1px 1px;
	border:				0;
	width:				auto;
	height:				25px;
}

.success, .error {
	font-size:			14px;
	color:				#007822;
	background:			#fcff88 url('<?=$cp_theme_url?>images/success.png') no-repeat 8px 10px;
	padding:			3px 15px 3px 30px;
	width:				396px;
	border:				none;
	margin:				25px 0;
}

.error {
	color:				#ce0000;
	background:			#fcff88 url('<?=$cp_theme_url?>images/error.png') no-repeat 8px 10px;
}

.success p, .error p {
	margin:				8px 0;
}
</style>
</head>
<body id="login" onload="<?=$cp_page_onload?>">

<div id="branding"><a href="http://expressionengine.com/"><img src="<?=$cp_theme_url?>images/ee_logo_branding.gif" width="250" height="28" alt="<?=lang('powered_by')?> ExpressionEngine" /></a></div>

<div id="content">

<div id="white">
	<?php if ($message_success != ''):?>
		<div class="success">
			<p><?=$message_success?></p>
		</div>
	<?php elseif ($message_error != ''):?>
		<div class="error">
			<p><?=$message_error?></p>
		</div>
	<?php endif;?>

	<p><a href="<?=BASE.AMP.'C=login'?>"><?=lang('return_to_login')?></a></p>
</div>

</div>
</body>
</html>