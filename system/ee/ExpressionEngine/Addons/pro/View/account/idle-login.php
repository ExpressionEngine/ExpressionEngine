<div id="idle-modal">

	<p><?=lang('session_timeout')?></p>
	<?=form_open(ee('CP/URL')->make('login/authenticate', ['hide_closer' => 'y']))?>
		<input type="hidden" name="username" id="username" value="<?=form_prep(ee()->session->userdata('username'))?>">
		<fieldset class="fieldset-required">
			<div class="field-instruct">
				<label for="logout-confirm-password"><?=sprintf(lang('password_for'), form_prep(ee()->session->userdata('username')));?></label>
			</div>
			<div class="field-control">
				<input type="password" name="password" value="" id="logout-confirm-password">
			</div>
		</fieldset>
	<?=form_close()?>
</div>
<script type="text/javascript">
	window.parent.postMessage({type: 'ee-pro-login-form-shown'})

	var loginform = document.querySelector('form')

	function submitloginform() {
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
				switch (result.messageType) {
					case 'success':
						window.parent.postMessage({type: 'eereauthenticate'})
						break;

					default:
						alert(result.message)
				}
			},

			error: function(data, textStatus, errorThrown) {
				alert(textStatus)
				console.log(textStatus, errorThrown)
			}
		})
	}

	window.addEventListener('message', (event) => {
		if(event.data && event.data.type && event.data.type == 'eeproprocessreauth') {
			submitloginform()
		}
	});

	loginform.addEventListener('submit', (event) => {
		event.preventDefault()
		submitloginform()
	})
</script>
