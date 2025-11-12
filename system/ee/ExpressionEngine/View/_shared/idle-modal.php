        <div id="idle-modal" class="modal-wrap modal-wrap--small modal-timeout hidden">
			<div class="modal modal--no-padding dialog dialog--warning">

			<div class="dialog__header">
				<div class="dialog__icon"><i class="fal fa-user-clock"></i></div>
				<h2 class="dialog__title"><?=sprintf(lang('log_into'), ee()->config->item('site_name'))?></h2>
			</div>

			<div class="dialog__body">
			<?=lang('session_timeout')?>
			</div>

			<?=form_open(ee('CP/URL')->make('login/authenticate'))?>
				<input type="hidden" name="username" value="<?=form_prep(ee()->session->userdata('username'))?>">
				<fieldset class="fieldset-required dialog-input-wrap">
					<legend class="sr-only"><?=sprintf(lang('log_into'), ee()->config->item('site_name'))?> form</legend>
					<div class="field-instruct">
						<label for="logout-confirm-password"><?=sprintf(lang('password_for'), form_prep(ee()->session->userdata('username')));?></label>
					</div>
					<div class="field-control">
						<input type="password" name="password" value="" id="logout-confirm-password" autocomplete="current-password">
					</div>
				</fieldset>

			<div class="dialog__actions">
				<div class="dialog__buttons">
						<?=form_submit('submit', lang('login'), 'class="button button--primary" data-submit-text="' . lang('login') . '" data-work-text="' . lang('authenticating') . '"')?>
				</div>
			</div>
			<?=form_close()?>
			</div>
		</div>
