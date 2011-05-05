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
		
		<div class="heading">
				<h2><?=lang('extensions')?></h2>
		</div>
		<div class="pageContents">

			<?php $this->load->view('_shared/message');?>
			<div class="clear_left"></div>
		
			<?php
				$this->table->set_template($cp_table_template);
				$this->table->set_heading(
											lang('extension_name'),
											lang('settings'),
											lang('documentation'),
											lang('version'),
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
							$extension['version'],
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

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file extensions.php */
/* Location: ./themes/cp_themes/default/addons/extensions.php */