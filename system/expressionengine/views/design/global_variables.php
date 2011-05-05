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
				<h2><?=lang('global_variables')?></h2>
		</div>
		<div id="new_global_var" class="pageContents">

		<?php $this->load->view('_shared/message');?>

		<?php
			$this->table->set_template(array('table_open' => '<table class="mainTable clear_left" cellspacing="0" cellpadding="0">'));
			$this->table->set_heading(
										lang('global_variables'),
										lang('global_variable_syntax'),
										lang('delete')
									);
									
			if ($global_variables_count >= 1)
			{
				foreach ($global_variables->result() as $variable)
				{
					$this->table->add_row(
						'<a href="'.BASE.AMP.'C=design'.AMP.'M=global_variables_update'.AMP.'variable_id='.$variable->variable_id.'">'.$variable->variable_name.'</a>', 
						'{'.$variable->variable_name.'}', 
						'<a href="'.BASE.AMP.'C=design'.AMP.'M=global_variables_delete'.AMP.'variable_id='.$variable->variable_id.'">'.lang('delete').'</a>'
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_global_variables'), 'colspan' => 3));
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

/* End of file global_variables.php */
/* Location: ./themes/cp_themes/default/design/global_variables.php */