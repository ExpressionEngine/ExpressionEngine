<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

			<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		    <div class="pageContents">
		
				<?=form_open('C=content_edit'.AMP.'M=delete_entries')?>
	
				<?php foreach($damned as $entry_id):?>
					<?=form_hidden('delete[]', $entry_id)?>
				<?php endforeach;?>

				<p><strong><?=$message?></strong></p>

				<?php if ($title_deleted_entry != ''):?>
					<p><?=$title_deleted_entry?></p>
				<?php endif;?>

				<p class="notice"><?=lang('action_can_not_be_undone')?></p>

				<p><?=form_submit('delete_members', lang('delete'), 'class="submit"')?></p>
	
				<?=form_close()?>

			</div>
		
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file delete_confirm.php */
/* Location: ./themes/cp_themes/default/content/delete_confirm.php */