<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="file_browser" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>

	<?=form_open('C=content_edit'.AMP.'M=delete_entries')?>

	<?php foreach($damned as $entry_id):?>
		<?=form_hidden('delete[]', $entry_id)?>
	<?php endforeach;?>

	<p class="container pad"><strong><?=$message?></strong></p>

	<?php if ($title_deleted_entry != ''):?>
		<p class="pad"><?=$title_deleted_entry?></p>
	<?php endif;?>

	<p class="container pad"><?=lang('action_can_not_be_undone')?></p>

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
/* Location: ./themes/cp_themes/mobile/tools/delete_confirm.php */