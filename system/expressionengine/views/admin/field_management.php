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

		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

		<?php $this->load->view('_shared/message');?>

		<?php
			$this->table->set_template($cp_table_template);
			$this->table->set_heading(
				array('data' => lang('id'), 'width' => '4%'),
				lang('field_label'),
				lang('field_name'),
				lang('order'),
				lang('field_type'),
				''
			);
			
			if (count($custom_fields) > 0)
			{
				foreach ($custom_fields as $field)
				{
					$this->table->add_row(
						$field['field_id'],
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=field_edit'.AMP.'field_id='.$field['field_id'].AMP.'group_id='.$group_id.'">'.$field['field_label'].'</a>',
						form_input(array(
							'name'			=> 'field_name', 
							'value'			=> '{'.$field['field_name'].'}', 
							'class'			=> 'input-copy',
							'data-original'	=> '{'.$field['field_name'].'}'
						)),
						$field['field_order'],
						$field['field_type'],
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=field_delete_confirm'.AMP.'field_id='.$field['field_id'].AMP.'group_id='.$group_id.'">'.lang('delete').'</a>'
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_field_groups'), 'colspan' => 5));
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

/* End of file field_management.php */
/* Location: ./themes/cp_themes/default/admin/field_management.php */