<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
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
	color:				#f62958;
	text-decoration:	underline;
}

a:hover {
	color:				#ff5479;
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

input, .error {
	font-size:			16px;
	border:				none;
	-webkit-border-radius: 6px;
	border-radius:		6px;
	padding:			10px;
	width:				430px;
	margin-right:		5px;
	color:				#333;
}

input[type=checkbox] {
	width:				auto;
}

span {
	margin-left:		20px;
}

input.submit {
	background:			#fc2e5a url('<?=$cp_theme_url?>images/submit_button_bg.gif') bottom left repeat-x;
	padding:			7px 16px 9px 16px;
	color:				#fff;
	font-weight:		bold;
	border:				0;
	width:				auto;
}

</style>
</head>
<body id="login" onload="<?=$cp_page_onload?>">

<div id="branding"><a href="http://ellislab.com/"><img src="<?=PATH_CP_GBL_IMG?>ee_logo_branding.gif" width="250" height="28" alt="<?=lang('powered_by')?> ExpressionEngine" /></a></div>

	<div id="content">
	
<?php if ($message != ''):?>
<div class='highlight'><?=$message?></div>
<?php endif;?>

<?=form_open('C=login'.AMP.'M=reset_password')?>

<?=form_hidden('resetcode', $resetcode)?>

<dl>
	<dt><?=lang('new_password')?>:</dt> 
	<dd>
		<?=form_password(array('style' => 'width:100%', 'size' => '35', 'dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => 80, 'autocomplete' => 'off'))?>
		<?=form_error('password')?>
	</dd>
	<dt><?=lang('new_password_confirm')?>:</dt>
	<dd>
		<?=form_password(array('style' => 'width:100%', 'size' => '35', 'dir' => 'ltr', 'name' => "password_confirm", 'id' => "password_confirm", 'maxlength' => 80, 'autocomplete' => 'off'))?>
		<?=form_error('password_confirm')?>
	</dd>
</dl>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?> <span><a href="<?=BASE.AMP.'C=login'?>"><?=lang('return_to_login')?></a></span></p>

</form>

</div>
</body>
</html>
