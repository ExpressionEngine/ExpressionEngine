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
		
		<div class="heading"><h2><?=lang('create_new_global_variable')?></h2></div>
		
		<div id="new_global_var" class="pageContents">

			<?=form_open('C=design'.AMP.'M=global_variables_create')?>
		<?php
		    $this->table->set_template($cp_table_template);
		    $this->table->set_heading(
                array('data' => lang('preference'), 'style' => 'width:50%;'),
				lang('setting')
			);

			$variable_name = array(
				'id'		=> 'variable_name',
				'name'		=> 'variable_name',
				'size'		=> 70,
				'class'		=> 'fullfield'
			);
			
					$this->table->add_row(array(
					lang('global_variable_name', 'global_variable_name').'<br />'.
					lang('template_group_instructions').' '.lang('undersores_allowed'),
					form_error('variable_name').
					form_input($variable_name)
				)
			);
			
				$variable_data = array(
				'id'		=> 'variable_data',
				'name'		=> 'variable_data',
				'cols'		=>	70,
				'rows'		=>	10,
				'class'		=> 'fullfield'
			);
			
					$this->table->add_row(array(
					lang('variable_data', 'variable_data').'<br />',
					form_error('variable_data').
					form_textarea($variable_data)
				)
			);
						echo $this->table->generate();
		?>


				<p class="centerSubmit"><?=form_submit('template', lang('create'), 'class="submit"')?></p>
	
			<?=form_close()?>
		
		</div> <!-- pageContents -->
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file global_variables_create.php */
/* Location: ./themes/cp_themes/corporate/design/global_variables_create.php */