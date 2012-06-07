<?php $this->load->view('account/_account_header');?>

	<div>
		<h3><?=lang('username_and_password')?></h3>

		<?=form_open('C=myaccount'.AMP.'M=update_username_password', '', $form_hidden)?>

		<?php if ($allow_username_change):?>
		<p>
			<?=lang('username', 'username')?>
			<?=form_input(array('id'=>'username','name'=>'username','class'=>'field','value'=>$username,'maxlength'=>50))?>
		</p>
		<?php endif;?>

		<p>
			<?=lang('screen_name', 'screen_name')?>
			<?=form_input(array('id'=>'screen_name','name'=>'screen_name','class'=>'field','value'=>$screen_name,'maxlength'=>50))?>
		</p>

		<fieldset>
			<legend><?=lang('password_change')?></legend>

			<div class="notice"><?=lang('password_change_exp')?></div>

			<p>
				<?=lang('new_password', 'password')?>
				<?=form_password(array('id'=>'password','name'=>'password','class'=>'field','value'=>'','maxlength'=>40))?>
			</p>

			<p>
				<?=lang('new_password_confirm', 'password_confirm')?>
				<?=form_password(array('id'=>'password_confirm','name'=>'password_confirm','class'=>'field','value'=>'','maxlength'=>40))?>
			</p>

			<?php if ($this->session->userdata('group_id') != 1):?>

			<div class="notice"><?=lang('existing_password_exp')?></div>

			<p>
				<?=lang('existing_password', 'current_password')?>
				<?=form_password(array('id'=>'current_password','name'=>'current_password','class'=>'field','value'=>'','maxlength'=>40))?>
			</p>

			<?php endif;?>

		</fieldset>

		<p class="submit"><?=form_submit('username_password', lang('update'), 'class="submit"')?></p>

		<?=form_close()?>
	</div>

<?php $this->load->view('account/_account_footer');