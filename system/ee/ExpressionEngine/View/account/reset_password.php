<?php $this->extend('_templates/login'); ?>

<div class="login__logo">
    <?php $this->embed('ee:_shared/ee-logo')?>
</div>

<div class="login__content reset-password">
	<h1 class="login__title"><?=lang('reset_password')?><span class="icon-reset"></span></h1>
	<?=ee('CP/Alert')->getAllInlines()?>
	<?=form_open(ee('CP/URL')->make('login/reset_password'))?>
		<fieldset>
			<div class="field-instruct">
			<?=lang('new_password', 'password')?>
			</div>
			<div class="field-control" style="position: relative;">
				<?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
			</div>
		</fieldset>
		<fieldset>
			<div class="field-instruct">
			<?=lang('new_password_confirm', 'password_confirm')?>
			</div>
			<div class="field-control" style="position: relative;">
				<?=form_password(array('dir' => 'ltr', 'name' => "password_confirm", 'id' => "password_confirm", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off'))?>
			</div>
		</fieldset>
		<fieldset class="last text-center">
			<?=form_hidden('resetcode', $resetcode)?>
			<?=form_submit('submit', lang('change_password'), 'class="button button--primary button--large button--wide" data-work-text="' . lang('updating') . '"')?>
		</fieldset>
	<?=form_close()?>
</div>

<script type="text/javascript">
	var EE = {
		cp: {
			validatePasswordUrl: "<?=ee('CP/URL', 'login/validate_password')->compile()?>"
		},
		lang: {
			password_icon: "<?=lang('password_icon')?>"
		}
	}

	// Check password strength indicator
	function passwordStrengthIndicator(field) {

		var form = field.parents('form'),
				action = form.attr('action'),
				data = form.serialize();

			if ( (typeof(EE.cp.validatePasswordUrl) != 'undefined') && (field.attr('name') == 'password') ) {
				$(field).parent('.field-control').css('position', 'relative');

				$.ajax({
					type: 'POST',
					url: EE.cp.validatePasswordUrl,
					dataType: 'json',
					data: data+'&ee_fv_field='+field.attr('name'),
					success: function (result) {
						if (result['rank'] == 0) {
							$('.rank-wrap').remove();
							return;
						} else {
							var rank_text = result['rank_text'].toLowerCase();
							var rank = result['rank'];
							var classList = 'status-tag '+rank_text;
							if (!$('.rank-wrap').length) {
								$(field).after('<div class="rank-wrap"><p class="'+classList+'"><span class="rank_text">'+rank_text+'</span></p></div>');
							} else {
								$('.rank-wrap > p').attr('class', classList);
								$('.rank-wrap .rank_text').text(rank_text);
							}
						}
					},
					error: function(err) {
						console.log('err', err);
					}
				})
			}
	}

	var passwordTimeout = null

	// Typing into the password field
	$('body').on('keyup', 'input[name="password"]', function() {

		var field = $(this);
		var val = $(this).val();
		clearTimeout(passwordTimeout)
		passwordTimeout = setTimeout(function() {
			if(val == 0) {
				if ($('.rank-wrap').length) {
					$('.rank-wrap').remove();
				}
			} else {
				passwordStrengthIndicator(field);
			}

			passwordTimeout = null
		}, 1000)
	});
</script>
