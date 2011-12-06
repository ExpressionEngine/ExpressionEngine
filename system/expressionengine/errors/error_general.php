<html>
<head>
<title>Error</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<?php if (defined('BASE')):?>
<link rel="stylesheet" href="<?php echo BASE.AMP.'C=css';?>" type="text/css" media="screen" title="Global Styles" charset="utf-8" />
<!--[if lte IE 7]>
<link rel="stylesheet" href="<?php echo BASE.AMP.'C=css'.AMP.'M=iefix';?>" type="text/css" media="screen" charset="utf-8" />
<![endif]-->
<?php endif;?>

<style type="text/css">

body {
	padding:0;
	margin:0 auto;
	font: 13px/20px normal Helvetica, Arial, sans-serif;
}

#error_content	{
	border:				#999 1px solid;
	background:			#FFF;
	padding:			20px 20px 12px 20px;
	margin:				50px auto;
	width:				80%;
}

h1 {
font-weight:		normal;
font-size:			14px;
color:				#990000;
margin:				0 0 4px 0;
}

body, html {
	background:		#27343C!important;
}

p {
	margin:			15px 0;
}
<?php if (defined('BASE')):?>
div#branding {
	background:#27343C url(../themes/cp_themes/default/images/backgrounds_sprite.png) repeat-x scroll 0 0;
	height:17px;
	margin-bottom:5px;
}

div#branding a img {
	outline:none;
	border:none;
	float:right;
	margin-right:10%;
}
<?php endif; ?>
</style>


</head>
<body>
<?php if (defined('BASE')):?><div id="branding"></div><?php endif; ?>

	<div id="error_content">
		<h1><?php echo $heading; ?></h1>
		<?php echo $message; ?>
	</div>
</body>
</html>