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


	<?=form_open('C=members'.AMP.'M=member_delete')?>

	<?php foreach($damned as $member_id):?>
		<?=form_hidden('delete[]', $member_id)?>
	<?php endforeach;?>

	<p class="pad"><strong><?=lang('delete_members_confirm')?></strong></p>
	<div class="pad container">
	<?=$user_name?>
	</div>

	<p class="notice pad"><?=lang('action_can_not_be_undone')?></p>

	<?php if(count($heirs) == 1):?>
	<p class="pad"><?=lang('heir_to_member_entries', 'heir').BR.form_dropdown('heir', $heirs)?></p>
	<?php elseif(count($heirs) > 1):?>
	<p class="pad"><?=lang('heir_to_members_entries', 'heir').BR.form_dropdown('heir', $heirs)?></p>
	<?php endif;?>

	<?=form_submit('delete_members', lang('delete'), 'class="whiteButton"')?>

	<?=form_close()?>



</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file delete_confirm.php */
/* Location: ./themes/cp_themes/mobile/members/delete_confirm.php */