<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="file_browser" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=content_publish"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>

	<p class="pad"><?=lang('autosave_warning_1')?></p>
	<p class="pad container"><?=lang('autosave_warning_2')?></p>
	<p class="pad"><?=lang('autosave_warning_3')?></p>
	<ul class="individual">
		<li><a href="<?=BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$this->input->get_post('channel_id').AMP.'entry_id='.$this->input->get_post('entry_id').AMP.'use_autosave=y'?>"><?=lang('yes')?></a></li>

		<li><a href="<?=BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$this->input->get_post('channel_id').AMP.'entry_id='.$this->input->get_post('entry_id').AMP.'use_autosave=n'?>"><?=lang('no')?></a></li>
	</ul>

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file autosave_options.php */
/* Location: ./themes/cp_themes/mobile/tools/autosave_options.php */