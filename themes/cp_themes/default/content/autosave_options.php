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

			<p><?=lang('autosave_warning_1')?></p>
			<p class="notice"><?=lang('autosave_warning_2')?></p>
			<p><?=lang('autosave_warning_3')?></p>
			<div class="cp_button"><a href="<?=BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$this->input->get_post('channel_id').AMP.'entry_id='.$this->input->get_post('entry_id').AMP.'use_autosave=y'?>"><?=lang('yes')?></a></div>
			<div class="cp_button"><a href="<?=BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$this->input->get_post('channel_id').AMP.'entry_id='.$this->input->get_post('entry_id').AMP.'use_autosave=n'?>"><?=lang('no')?></a></div>

			<div class="clear"></div>
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file autosave_options.php */
/* Location: ./themes/cp_themes/default/content/autosave_options.php */