<?php $this->load->view('account/_header')?>

<?=form_open('C=myaccount'.AMP.'M=select_avatar', array('id'=>'browse_avatar_form'), $form_hidden)?>

<?php if ($pagination != ''):?>
	<?=$pagination?>
<?php endif;?>

<?=$this->table->generate($this->table->make_columns($avatars, 3))?>

<?php if ($pagination != ''):?>
	<?=$pagination?>
<?php endif;?>

<?=form_submit('edit_profile', lang('choose_selected'), 'class="whiteButton"')?>

<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file browse_avatars.php */
/* Location: ./themes/cp_themes/default/account/browse_avatars.php */