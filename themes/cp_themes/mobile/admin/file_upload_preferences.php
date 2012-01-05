<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="translate" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=admin_system"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	
	<?php
								
		if (count($upload_locations) > 0)
		{
			foreach ($upload_locations as $upload_location):?>
				<div class="label">
					<label><?=$upload_location['id'].' '.$upload_location['name']?></label>
				</div>
				<ul>
					<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=edit_upload_preferences'.AMP.'id='.$upload_location['id']?>" title="<?=lang('edit')?>"><?=lang('edit')?></a></li>
					<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=delete_upload_preferences_conf'.AMP.'id='.$upload_location['id']?>" title="<?=lang('delete')?>"><?=lang('delete')?></a></li>
				</ul>
			<?php endforeach;
		}
		else
		{
			echo '<p class="pad container">'.lang('no_upload_prefs').'</p>';
		}
	?>	
	
	
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_upload_preferences.php */
/* Location: ./themes/cp_themes/mobile/admin/file_upload_preferences.php */