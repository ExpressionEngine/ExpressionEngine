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

				<?=form_open('C=design'.AMP.'M=template_delete'.AMP.'tgpref='.$group_id, '', $form_hidden)?>

				<p><strong><?=lang('delete_this_template')?></strong></p>
		
				<p><?=$template_name?></p>
				<?php if ($file !== FALSE): ?>
				<p><strong><?=lang('file_exists_warning')?></strong></p>
				<?php endif; ?>
		
				<p class="notice"><?=lang('action_can_not_be_undone')?></p>

				<p><?=form_submit('delete_template', lang('delete'), 'class="submit"')?></p>
	
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

/* End of file template_delete_confirm.php */
/* Location: ./themes/cp_themes/default/members/template_delete_confirm.php */