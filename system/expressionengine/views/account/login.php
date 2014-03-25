<!doctype html>
<html>
	<head>
		<?=$this->view->head_title($cp_page_title)?>
		<?=$this->view->head_link('css/v3/login.css'); ?>
		<?=$this->view->script_tag('jquery/jquery.js')?>

	</head>
	<body>
		<section class="wrap">
			<div class="box snap">
				<h1>Login to <?=$site_label?> <span class="ico locked"></span></h1>
				<?php if ($message != ''):?>
					<div class="error-msg"><p><b>!!</b> <?=$message?></p></div>
				<?php endif;?>
				<?=form_open('C=login'.AMP.'M=authenticate', array(), array('return_path' => $return_path))?>
					<fieldset>
						<?=lang('username', 'username')?>
						<?=form_input(array('dir' => 'ltr', 'name' => "username", 'id' => "username", 'value' => $username, 'maxlength' => 50))?>
					</fieldset>
					<fieldset class="last">
						<?=lang('password', 'password')?>
						<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => 40, 'autocomplete' => 'off'))?>
						<em><a href="<?=cp_url('/login/forgotten_password_form')?>"><?=lang('forgotten_password')?></a></em>
					</fieldset>
					<?php if ($this->config->item('cp_session_type') == 'c'):?>
					<fieldset class="options">
						<label for="remember_me"><input type="checkbox" name="remember_me" value="1" id="remember_me"> <?=lang('remember_me')?></label>
					</fieldset>
					<?php endif;?>
					<fieldset class="form-ctrls">
						<?=form_submit('submit', $btn_label, 'class="'.$btn_class.'"')?>
					</fieldset>
				<?=form_close()?>
			</div>
			<section class="bar snap">
				<p class="left"><b>ExpressionEngine</b> <!-- <span><b>3</b>.0</span> --></p>
				<p class="right">&copy;2003&mdash;<?=ee()->localize->format_date('%Y')?> <a href="http://ellislab.com/expressionengine" rel="external">EllisLab</a>, Inc.</p>
			</section>
		</section>
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" type="text/javascript"></script>
		<?=$this->view->script_tag('v3/cmon-ck.js')?>
		<script>
			$(document).ready(function()
			{
				document.getElementById('<?=$focus_field?>').focus();

				$('form').submit(function(event)
				{
					$('input.btn', this).addClass('work').attr('value', 'authenticating...')
				});
			});
		</script>
		<?php
		if (isset($script_foot))
		{
			echo $script_foot;
		}
		?>
	</body>
</html>