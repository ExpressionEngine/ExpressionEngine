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
		<div id="new_global_var" class="pageContents">

			<?=form_open('C=design'.AMP.'M=global_variables_update')?>
				<?=form_hidden('variable_id', $variable_id)?>
				<p>
				<label for="variable_name"><?=lang('global_variable_name')?></label><br />
				<?=lang('template_group_instructions') . ' ' . lang('undersores_allowed')?><br />
				<?=form_input(array('id'=>'variable_name','name'=>'variable_name','size'=>70,'class'=>'field','value'=>$variable_name))?>				
				</p>
				<p>
				<label for="variable_data"><?=lang('variable_data')?></label><br />
				<?=form_textarea(array('id'=>'variable_data','name'=>'variable_data','cols'=>70,'rows'=>10,'class'=>'field','value'=>$variable_data))?>
				</p>
				<p><?=form_submit('template', lang('update'), 'class="submit"')?></p>
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