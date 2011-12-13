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
		$this->table->set_heading($table_headings);
		echo $this->table->generate($fieldtypes);
	?>

</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file fieldtypes.php */
/* Location: ./themes/cp_themes/mobile/addons/fieldtypes.php */