<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members<?=AMP?>M=custom_profile_fields" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>


	<?=form_open($form_action, '', $form_hidden)?>

	<div class="container pad">
		<p><?=lang('delete_field')?></p>
		<p><em><?=$field_name?></em></p>
	</div>

	<p class="pad"><?=lang('delete_field_confirmation')?></p>

	<p class="notice pad"><?=lang('action_can_not_be_undone')?></p>

	<p><?=form_submit('delete_members', lang('delete'), 'class="submit"')?></p>

	<?=form_close()?>



</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_profile_field.php */
/* Location: ./themes/cp_themes/mobile/members/edit_profile_field.php */