<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=addons_fieldtypes" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

	<?=form_open('C=addons_fieldtypes'.AMP.'M=global_settings'.AMP.'ft='.$_ft_name)?>
		<?=$_ft_settings_body?>
		<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
	<?=form_close()?>
	
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file module_cp_container.php */
/* Location: ./themes/cp_themes/mobile/addons/fieldtype_global_settings.php */