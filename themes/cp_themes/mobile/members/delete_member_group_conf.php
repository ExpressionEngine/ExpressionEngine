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

	<?=form_open('C=members'.AMP.'M=delete_member_group'.AMP.'group_id='.$group_id, '', $form_hidden)?>

	<div class="pad container">
		<p><?=$this->lang->line('delete_member_group_confirm')?></p>
		<p><em><?=$group_title?></em></p>
	</div>
	
	<p class="pad"><?=$this->lang->line('action_can_not_be_undone')?></p>

	<?php if ($member_count > 0):?>
		<p class="pad"><?=str_replace('%x', $member_count , $this->lang->line('member_assignment_warning'))?>
		<ul>
			<li><?=form_dropdown('new_group_id', $new_group_id)?></li>
		</ul>
	<?php endif;?>

	<?=form_submit('delete', $this->lang->line('delete'), 'class="whiteButton"')?>

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