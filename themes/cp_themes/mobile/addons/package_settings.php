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

	<?=form_open($form_action)?>
	
	<?php
	$this->table->set_heading(
							array('data' => lang('component'), 'style' => 'width:40%;'),
							array('data' => lang('status')),
							array('data' => lang('current_status'))
							);

	foreach ($components as $comp => $info)
	{
		
		$this->table->add_row(
								lang($comp),
								form_radio('install_'.$comp, 'install', $info['installed']).NBS.lang('install').
								NBS.NBS.NBS.NBS.NBS.
								form_radio('install_'.$comp, 'uninstall', ! $info['installed']).NBS.lang('uninstall'),
								$info['installed'] ? lang('installed') : lang('not_installed')
							);
	}
	echo $this->table->generate();
	$this->table->clear();
	?>

	
	<p><?=form_submit('submit', lang('submit'), 'class="whiteButton"')?></p>

	<?=form_close()?>

</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file package_settings.php */
/* Location: ./themes/cp_themes/mobile/addons/package_settings.php */