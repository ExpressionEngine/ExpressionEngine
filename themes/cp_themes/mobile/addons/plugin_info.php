<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=addons_plugins" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	<?php
		$this->table->set_heading(lang('plugin_information'), '');

		foreach($plugin as $key => $data)
		{
			$this->table->add_row(
				lang($key) ? lang($key) : ucwords(str_replace("_", " ", $key)),
				$data
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

/* End of file plugin_info.php */
/* Location: ./themes/cp_themes/mobile/addons/plugin_info.php */