<!doctype html>
<html>
	<head>
		<title><?=$title?></title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
		<link href="<?=$theme_url?>/ee/cp/css/common.min.css" rel="stylesheet">
		<?php
        if (isset($extra_header)) {
            echo $extra_header;
        }

        if (isset($refresh) && $refresh === true) {
            if ($ajax_progress) {
                $refresh_url .= '&ajax_progress=yes';
            }
            echo '<meta http-equiv="refresh" content="1;url=' . $refresh_url . '" />';
        }
        ?>
	</head>
	<body class="installer-page">
		<section class="flex-wrap">
			<section class="wrap">
				<div class="login__logo"><?=$logo?></div>
				<?=$content?>
			</div>
			<section class="bar">
				<p style="float: left;"><a href="https://expressionengine.com/" rel="external"><b>ExpressionEngine</b></a></p>
				<p style="float: right;">&copy;<?=date('Y')?> <a href="https://packettide.com/" rel="external">Packet Tide</a>, LLC</p>
			</section>
		</section>

		<script src="<?=$javascript_path?>jquery/jquery.js" type="text/javascript"></script>
		<script src="<?=$javascript_path?>common.js" type="text/javascript"></script>

		<script>
			var eyeOpen = '<?=$theme_url.'/ee/asset/img/eye-open.svg'?>',
				eyeClosed = '<?=$theme_url.'/ee/asset/img/eye-closed.svg'?>'

			var passwordInput = $('input[name="password"]'),
				passwordInputContainer = $('input[name="password"]').closest('.field-control'),
				eyeImg = '<img src="' + eyeOpen + '" id="eye" />',
				eyeIsOpen = false

			$(passwordInputContainer).css({'position': 'relative'})
			$(eyeImg).insertAfter(passwordInput)

			$('#eye').click(function () {
				$('input[name="password"]').attr('type', ($('input[name="password"]').attr('type') === 'password' ? 'text' : 'password'));
				$(this).attr('src', eyeIsOpen ? eyeOpen : eyeClosed)
				eyeIsOpen = !eyeIsOpen
		    });			
		</script>

	</body>
</html>
