<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title><?=$cp_page_title?> | ExpressionEngine</title>
<style type="text/css">

body {
	background-color:	#27343C;
	font-family: 		Helvetica, Arial, sans-serif;
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

input[type=text],input[type=password], .error {
	font-family: 		Helvetica, Arial, sans-serif;
	font-size:			1.2em;
	border:				none;
	-webkit-border-radius: 16px;
	-moz-border-radius:	16px;
	padding:			10px;
	width:				430px;
	margin-right:		5px;
}

.error {
	color:				#ff0000;
}

input[type=checkbox] {
	width:				auto;
}

span {
	margin-left:		20px;
}

input.submit {
	background:			#80a210 url('<?=$cp_theme_url?>images/btn_grad.png') top left repeat-x;
	display: 			inline-block;
	padding:			3px 31px 3px 31px;
	color:				#fff;
	font-family: 		Helvetica, Arial, sans-serif;
	font-size:			12px;
	line-height: 		1;
	font-weight: 		bold;
	letter-spacing: 	0.9px;
	text-transform:		uppercase;
	border:				none;
	height:				28px;
	text-align: 		center;
	cursor:				pointer;
	-moz-border-radius: 14px;
	-webkit-border-radius: 14px;
	-moz-box-shadow:	0 1px 1px #2b3941;
	-webkit-box-shadow:	0 1px 1px #2b3941;
	text-shadow:		-1px -1px 1px #4d5c08;
}

p input.submit {
	margin:				15px 10px 0 0;
}

input.submit:hover{
	background:			#95a700 url('<?=$cp_theme_url?>images/btn_grad.png') 0 -28px  repeat-x;
}

</style>
</head>
<body id="login" onload="<?=$cp_page_onload?>">

<div id="branding"><a href="http://expressionengine.com/"><img src="<?=$cp_theme_url?>images/ee_logo_branding.gif" width="250" height="28" alt="<?=lang('powered_by')?> ExpressionEngine" /></a></div>

	<div id="content">

		<div class="error">
			<?php foreach ($message as $message):?>
				<p><?=$message?></p>
			<?php endforeach;?>
			<ul>
				<?php foreach ($notices as $notice):?>
					<li><?=$notice?></li>
				<?php endforeach;?>
			</ul>
		</div>
		
		<?=form_open('C=login'.AMP.'M=update_un_pw', array(), $hidden)?>
		
		<dl>
			<?php if ($new_username_required):?>
			<dt><?=lang('existing_username')?>: <?=$username?><br />
				<?=lang('choose_new_un', 'new_username')?>:</dt>
			<dd><?=form_input('new_username', $new_username)?></dd>
			<?php endif;?>
			
			<?php if ($new_password_required):?>
			<dt><?=lang('existing_password')?>: <?=$password?><br />
				<?=lang('choose_new_pw', 'new_password')?>:</dt>
			<dd><?=form_password('new_password', $new_password)?></dd>
			<dt><?=lang('confirm_new_pw', 'confirm_new_pw')?></dt>
			<dd><?=form_password('new_password_confirm')?></dd>
			<?php endif;?>
		</dl>
		<p><?=form_submit('submit', 'Submit', 'class="submit"')?></p>
		<?=form_close()?>

	</div>

</body>
</html>