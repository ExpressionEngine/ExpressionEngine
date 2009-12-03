<?php $this->load->view('account/_header')?>


<?=form_open('C=myaccount'.AMP.'M=update_username_password', '', $form_hidden)?>

<?php if ($allow_username_change):?>
<div class="label" style="margin-top:15px">
	<?=lang('username', 'username')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'username','name'=>'username','class'=>'field','value'=>$username,'max_length'=>50))?></li>
</ul>
<?php endif;?>

<div class="label">
	<?=lang('screen_name', 'screen_name')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'screen_name','name'=>'screen_name','class'=>'field','value'=>$screen_name,'max_length'=>50))?></li>
</ul>

<h3 class="pad"><?=lang('password_change')?></h3>

<div class="pad"><?=lang('password_change_exp')?></div>
<div class="pad"><?=lang('password_change_requires_login')?></div>

<div class="label">
	<?=lang('new_password', 'password')?>
</div>
<ul>
	<li><?=form_password(array('id'=>'password','name'=>'password','class'=>'field','value'=>'','max_length'=>32))?></li>
</ul>

<div class="label">
	<?=lang('new_password_confirm', 'password_confirm')?>
</div>
<ul>
	<li><?=form_password(array('id'=>'password_confirm','name'=>'password_confirm','class'=>'field','value'=>'','max_length'=>32))?></li>
</ul>

<?php if ($this->session->userdata('group_id') != 1):?>

<div class="pad"><?=lang('existing_password_exp')?></div>

<div class="label">
	<?=lang('existing_password', 'current_password')?>
</div>
<ul>
	<li><?=form_password(array('id'=>'current_password','name'=>'current_password','class'=>'field','value'=>'','max_length'=>32))?></li>
</ul>

<?php endif;?>

<?=form_submit('username_password', lang('update'), 'class="whiteButton"')?>

<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file username_password.php */
/* Location: ./themes/cp_themes/default/account/username_password.php */