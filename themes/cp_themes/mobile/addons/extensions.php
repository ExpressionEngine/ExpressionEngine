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
			$this->table->set_heading(
										lang('extension_name'),
										lang('settings'),
										lang('documentation'),
										lang('status')
									);
								
			if (count($extension_info) >= 1)
			{
				foreach ($extension_info as $filename => $extension)
				{
					$this->table->add_row(
						$extension['name'],
						$extension['settings_enabled'] ? '<a href="'.$extension['settings_url'].'">'.lang('settings').'</a>' : $extension['no_settings'],
						$extension['documentation'] ? '<a href="'.$extension['documentation'].'" rel="external">'.lang('documentation').'</a>' : '--',
						($extensions_enabled) ? lang($extension['status']).' (<a href="'.BASE.AMP.'C=addons_extensions'.AMP.'M=toggle_extension'.AMP.'which='.$filename.'">'.lang($extension['status_switch']).'</a>)' : lang($extension['status'])
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_extensions_exist'), 'colspan' => 4));
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

/* End of file extensions.php */
/* Location: ./themes/cp_themes/mobile/addons/extensions.php */