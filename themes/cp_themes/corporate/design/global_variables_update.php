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
				<h2><?=lang('global_var_update')?></h2>
		</div>
		<div class="pageContents">

			<?=form_open('C=design'.AMP.'M=global_variables_update')?>
				<?=form_hidden('variable_id', $variable_id)?>
				<?php $this->table->set_template(array('table_open' => '<table class="mainTable clear_left" cellspacing="0" cellpadding="0">'));
				$this->table->set_heading(NBS, NBS);
				
				$this->table->add_row(array(
						lang('variable_name', 'global_variable_name'),
						lang('template_group_instructions') . ' ' . lang('undersores_allowed').BR.
						form_input(array('id'=>'variable_name','name'=>'variable_name','size'=>70,'class'=>'field','value'=>$variable_name))
					)
				);

				$this->table->add_row(array(
						lang('variable_data', 'variable_data'),
						form_textarea(array('id'=>'variable_data','name'=>'variable_data','cols'=>70,'rows'=>10,'class'=>'field','value'=>$variable_data))
					)
				);
				
				echo $this->table->generate();
				?>				
				<?=form_submit('template', lang('update'), 'class="submit"')?>
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

/* End of file global_variables_update.php */
/* Location: ./themes/cp_themes/default/design/global_variables_update.php */