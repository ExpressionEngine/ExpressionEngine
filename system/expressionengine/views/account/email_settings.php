<?php $this->load->view('account/_account_header');?>

	<div>
		<h3><?=lang('email_settings')?></h3>

		<?=form_open('C=myaccount'.AMP.'M=update_email', '', $form_hidden)?>

		<p>
			<?=form_label(required().lang('email'), 'email')?>
			<?=form_input(array('id'=>'email','name'=>'email','class'=>'field','value'=>$email,'maxlength'=>72))?>
		</p>

		<?php if ($this->session->userdata('group_id') != 1):?>
		<p><em class="notice"><?=lang('existing_password_email')?></em><br />
			<?=form_label(lang('existing_password'), 'password')?>
			<?=form_password(array('id'=>'password','name'=>'password','class'=>'password','value'=>'','maxlength'=>40))?>
		</p>
		<?php endif;?>

		<fieldset><legend><?=lang('email_options')?></legend>
		<table style="width:100%">
			<tbody>
		<?php foreach($checkboxes as $checkbox):?>
			<tr><td>
			<?=form_checkbox(array('id'=>$checkbox,'name'=>$checkbox,'value'=>$checkbox, 'checked'=>($$checkbox=='y') ? TRUE : FALSE))?>
			</td><td>
			<strong><?=lang($checkbox)?></strong>
			</td></tr>
		<?php endforeach;?>
			</tbody>
		</table>
		</fieldset>

		<p class="submit"><?=form_submit('edit_profile', lang('update'), 'class="submit"')?></p>

		<?=form_close()?>
	</div>

<?php $this->load->view('account/_account_footer');