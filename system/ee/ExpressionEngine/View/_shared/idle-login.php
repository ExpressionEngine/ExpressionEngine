<div id="idle-modal">
	<div class="modal modal--no-padding dialog dialog--warning">
		<div class="dialog__header">
			<div class="dialog__icon"><i class="fas fa-user-clock"></i></div>
			<h2 class="dialog__title"><?=sprintf(lang('log_into'), ee()->config->item('site_name'))?></h2>
		</div>

		<div class="dialog__body">
		<?=lang('session_timeout')?>
		</div>

		<?=form_open(ee('CP/URL')->make('login/authenticate'))?>
			<div class="dialog__actions">
				<input type="hidden" name="username" id="username" value="<?=form_prep(ee()->session->userdata('username'))?>">
				<fieldset class="fieldset-required">
					<div class="field-instruct">
						<label for="logout-confirm-password"><?=sprintf(lang('password_for'), form_prep(ee()->session->userdata('username')));?></label>
					</div>
					<div class="field-control">
						<input type="password" name="password" value="" id="logout-confirm-password">
					</div>
				</fieldset>

				<div class="dialog__buttons">
					<?=form_submit('submit', lang('login'), 'class="button button--primary" data-submit-text="' . lang('login') . '" data-work-text="' . lang('authenticating') . '"')?>
				</div>
			</div>
		<?=form_close()?>
	</div>
</div>
<script>
	var loginform = document.querySelector('form')
	loginform.addEventListener('submit', function(event) {
		event.preventDefault()
		var action = loginform.action,
			username = document.getElementById('username').value,
			password = document.getElementById('logout-confirm-password').value,
			csrf = EE.CSRF_TOKEN;
		
		$.ajax({
			type: 'POST',
			url: action,
			dataType: 'json',
			data: {
				csrf_token: csrf,
				username: username,
				password: password,
			},
			success: function(result) {
				console.log(result)
				switch (result.messageType) {
					case 'success':
						window.parent.postMessage({type: 'eereauthenticate'})
						break;

					default:
						alert(result.message)
				}
			},

			error: function(data, textStatus, errorThrown) {
				console.log(textStatus, errorThrown)
			}
		})
	})
</script>