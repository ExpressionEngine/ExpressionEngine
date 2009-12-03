<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>


	<?=form_open('C=design'.AMP.'M=template_group_delete', '', $form_hidden)?>
	
	<div class="container pad">
		<p><strong><?=lang('delete_this_group')?></strong> <?=$template_group_name?></p>

		<?php if ($file_folder == TRUE): ?>
			<p><strong><?=lang('folder_exists_warning')?></strong></p>
		<?php endif; ?>

		<p class="notice"><?=lang('action_can_not_be_undone')?></p>
	</div>

	<p><?=form_submit('delete_template_group', lang('delete'), 'class="whiteButton"')?></p>

	<?=form_close()?>


</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file template_group_delete_confirm.php */
/* Location: ./themes/cp_themes/mobile/design/template_group_delete_confirm.php */