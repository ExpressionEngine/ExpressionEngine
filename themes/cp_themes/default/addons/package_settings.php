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
			<h2 class="edit"><?=$cp_page_title?></h2>
		</div>
			<div class="pageContents">
		
				<?=form_open($form_action)?>
				
				<?php
				$this->table->set_template($cp_table_template);
				$this->table->set_heading(
										array('data' => lang('component'), 'style' => 'width:40%;'),
										array('data' => lang('status')),
										array('data' => lang('current_status'))
										);

				foreach ($components as $comp => $info)
				{
					$fields  = form_radio('install_'.$comp, 'install', $info['installed']).NBS.lang('install').
											NBS.NBS.NBS.NBS.NBS.
											form_radio('install_'.$comp, 'uninstall', ! $info['installed']).NBS.lang('uninstall');
					
					if (isset($required[$comp]) && count($required[$comp]))
					{
						$fields = lang('required_by').NBS.implode(',', $required[$comp]);
					}
					
					$this->table->add_row(
											lang($comp),
											$fields,
											$info['installed'] ? lang('installed') : lang('not_installed')
										);
				}
				echo $this->table->generate();
				$this->table->clear();
				?>

				
				<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
	
				<?=form_close()?>

			</div>
		
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file delete_confirm.php */
/* Location: ./themes/cp_themes/default/addons/package_settings.php */