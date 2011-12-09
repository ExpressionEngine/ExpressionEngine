<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=addons" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

	<?php
		$this->table->set_heading(array('data' => lang('accessory_name'), 'width' => '50%'), lang('available_to_member_groups'), lang('specific_page'), lang('status'));
		
		foreach ($accessories as $accessory)
		{
			$title = ($accessory['acc_pref_url']) ? "<a href='{$accessory['acc_pref_url']}'>{$accessory['name']}</a>" : $accessory['name'];

			$this->table->add_row(
									"<strong>{$title}</strong> ({$accessory['version']})<br />{$accessory['description']}",
									(count($accessory['acc_member_groups']) > 0) ? ul($accessory['acc_member_groups'], array('style'=>'list-style:disc!important; margin-left: 15px;')) : '',
									(count($accessory['acc_controller']) > 0) ? ul($accessory['acc_controller'], array('style'=>'list-style:disc!important; margin-left: 15px;')) : '',
									"<a href='{$accessory['acc_install']['href']}'>{$accessory['acc_install']['title']}</a>"
								);
	
		}
		
		echo $this->table->generate();
	?>

</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file accessories.php */
/* Location: ./themes/cp_themes/mobile/addons/accessories.php */