<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Error</title>
	
	<style type="text/css" media="screen">
		html, body, h1, div {
			margin:				0;
			padding:			0;
		}

		body {
			background-color:	#27343C;
			font-family:		"Helvetica Neue", Arial, Helvetica, Geneva, sans-serif;
			text-align:			center;
			font-size:			12px;
		}
		
		#branding {
			background:			#27343c url('../images/branding_bg.gif') top left repeat-x;
			text-align:			right;
			padding-right:		125px;
		}

		#branding img {
			border:				0;
		}

		#error {
			margin:				100px auto 0 auto;
			overflow:			hidden;
			text-align:			left;
			width:				600px;

			-webkit-border-radius: 3px;
			border-radius:		3px;

			-webkit-box-shadow: 0 1px 3px #171E23;
			-moz-box-shadow:	0 1px 3px #171E23;
			box-shadow:			0 1px 3px #171E23;
		}

		#message {
			background:			#e8e8e8;
			color:				#444;
			padding:			10px;
			text-shadow:		0 1px 0 #fff;
		}

		h1 {
			color:				#ecf1f3;
			padding:			10px;
			font-size:			14px;
			font-weight:		normal;
			text-shadow:		0 1px 0 #000;

			border-top:			1px solid #DB2C38;
			border-bottom:		1px solid #fff;

			background:			#AE232C;
			background:			-webkit-gradient(linear, 0 0, 0 100%, from(#C22731), to(#991F27));
			background:			-moz-linear-gradient(#C22731, #991F27);
		}

		a:link, a:visited {
			color:				#f62958;
			text-decoration:	none;
		}

		a:hover {
			color:				#ff5479;
			text-decoration:	underline;
		}

		h1 a:link,
		h1 a:visited {
			font-size:			12px;
			float:				right;
			color:				inherit;
			padding-top:		1px;
			text-decoration:	underline;
		}
	</style>

</head>
<body>

<div id="branding"><a href="http://ellislab.com/"><img src="<?=PATH_CP_GBL_IMG?>ee_logo_branding.gif" width="250" height="28" alt="<?=lang('powered_by')?> ExpressionEngine" /></a></div>

<div id="content">
	<div id="error">	
		<h1><?=$heading?><?=$homepage?></h1>

		<div id="message">
			<?=$message?>
		</div>

	</div>
</div>

</body>
</html>

