<?php $this->load->view('account/_header')?>

<?=form_open('C=myaccount'.AMP.'M=update_email', '', $form_hidden)?>

<div class="label" style="margin-top:15px">
	<?=form_label(required().lang('email'), 'email')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'email','name'=>'email','class'=>'field','value'=>$email,'maxlength'=>72))?></li>
</ul>

<?php if ($this->session->userdata('group_id') != 1):?>
<p class="pad"><em class="notice"><?=lang('existing_password_email')?></em></p>
<div class="label">
	<?=form_label(lang('existing_password'), 'password')?>
</div>
<ul>
	<li><?=form_password(array('id'=>'password','name'=>'password','class'=>'password','value'=>'','maxlength'=>40))?></li>
</ul>
<?php endif;?>
<ul>
<?php foreach($checkboxes as $checkbox):?>
	<li><?=form_checkbox(array('id'=>$checkbox,'name'=>$checkbox,'value'=>$checkbox, 'checked'=>($$checkbox=='y') ? TRUE : FALSE))?> <?=lang($checkbox)?></li>
<?php endforeach;?>
</ul>	

<?=form_submit('edit_profile', lang('update'), 'class="whiteButton"')?>

<?=form_close()?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file email_settings.php */
/* Location: ./themes/cp_themes/default/account/email_settings.php */