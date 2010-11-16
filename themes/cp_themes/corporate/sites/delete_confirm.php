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
		
				<?=form_open('C=sites'.AMP.'M=delete_site', '', array('site_id' => $site_id))?>

				<p><strong><?=$message?></strong></p>

				<p class="notice"><?=lang('action_can_not_be_undone')?></p>

				<p><?=form_submit('delete_site', lang('delete_site'), 'class="submit"')?></p>
	
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

/* End of file view_members.php */
/* Location: ./themes/cp_themes/default/members/view_members.php */