<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>


	<?php if ($fields->num_rows() > 0):

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
	
		<p><a class="grayButton" href="<?=BASE.AMP.'C=members'.AMP.'M=edit_field_order'?>"><?=lang('edit_field_order')?></a></p>
	
	<?php else:?>
		<p class="notice"><?=lang('no_custom_profile_fields')?></p>
	<?php endif;?>








</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file custom_profile_fields.php */
/* Location: ./themes/cp_themes/mobile/members/custom_profile_fields.php */	