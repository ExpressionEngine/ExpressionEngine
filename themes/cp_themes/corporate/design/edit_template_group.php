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
			<h2><?=lang('edit_template_group_form')?></h2>
	</div>

	<div class="pageContents">
		<?=form_open('C=design'.AMP.'M=edit_template_group'.AMP.'tgpref='.$form_hidden['group_id'], '', $form_hidden)?>
		<?php
		    $this->table->set_template($cp_pad_table_template);
		    $this->table->set_heading(
                array('data' => lang('preference'), 'style' => 'width:50%;'),
				lang('setting')
			);
		
			// Name of Template Group
			$group_info = array(
				'id'		=> 'group_name',
				'name'		=> 'group_name',
				'size'		=> 30,
				'maxlength'	=> 50,
				'value'		=> set_value('group_name', $group_name)
			);
		
			$this->table->add_row(array(
					lang('name_of_template_group', 'name_of_template_group').'<br />'.
					lang('template_group_instructions').' '.lang('undersores_allowed'),
					form_error('group_name').
					form_input($group_info)
				)
			);
		
			// Is this the site index?
			$checkbox = array(
							'name'        => 'is_site_default',
							'value'       => 'y',
							'checked'     => set_checkbox('is_site_default', 'y', $is_default)
			  			);
		
			$this->table->add_row(array(
					lang('is_site_default', 'is_site_default'),
					form_checkbox($checkbox)
				)
			);
		
			echo $this->table->generate();
		?>

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

/* End of file edit_template_group.php */
/* Location: ./themes/cp_themes/default/design/edit_template_group.php */