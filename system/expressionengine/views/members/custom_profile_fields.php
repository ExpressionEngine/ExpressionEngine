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

		<div class="heading"><h2 class="edit"><?=lang('custom_profile_fields')?></h2></div>
		<div class="pageContents">

		<?php $this->load->view('_shared/message');?>

		<div class="clear_left"></div>
		
		<?php if ($fields->num_rows() > 0):

			$this->table->set_template($cp_table_template);
			$this->table->set_heading(
				lang('field_id'), 
				lang('fieldlabel'), 
				lang('fieldname'), 
				lang('order'), 
				'',
				''
			);
	
			foreach ($fields->result() as $field)
			{
				$this->table->add_row(
								$field->m_field_id,
								$field->m_field_label,
								$field->m_field_name,
								$field->m_field_order,
								'<a href="'.BASE.AMP.'C=members'.AMP.'M=edit_profile_field'.AMP.'m_field_id='.$field->m_field_id.'">'.lang('edit').'</a>',
								'<a href="'.BASE.AMP.'C=members'.AMP.'M=delete_profile_field_conf'.AMP.'m_field_id='.$field->m_field_id.'">'.lang('delete').'</a>'
								);					
			}

			echo $this->table->generate();
		?>	
		
			<p class="shun"><a href="<?=BASE.AMP.'C=members'.AMP.'M=edit_field_order'?>"><?=lang('edit_field_order')?></a></p>
		
		<?php else:?>
			<p class="notice"><?=lang('no_custom_profile_fields')?></p>
		<?php endif;?>
	
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file custom_fields.php */
/* Location: ./themes/cp_themes/default/members/custom_fields.php */