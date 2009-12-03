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

		<div class="heading"><h2><?=$cp_page_title?></h2>
			<div class="buttonRightHeader"><a class="button" href="<?=BASE.AMP.'C=admin_content'.AMP.'M=edit_custom_category_field'.AMP.'group_id='.$group_id?>"><?=lang('create_new_custom_field')?></a></div>
		</div>
		
		<div class="pageContents">

			<h3><?=lang('category_group').': '.$group_name?></h3><br />

		<?php $this->load->view('_shared/message');?>

		<?php
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(
										lang('field_label'),
										lang('field_name'),
										lang('field_type'),
										''
									);
									
			if (count($custom_fields) > 0)
			{
				foreach ($custom_fields as $field)
				{
					$this->table->add_row(
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=edit_custom_category_field'.AMP.'group_id='.$group_id.AMP.'field_id='.$field['field_id'].'">'.$field['field_id'].' '.$field['field_label'].'</a>',
						$field['field_name'],
						$field['field_type'],
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=delete_custom_category_field_confirm'.AMP.'group_id='.$group_id.AMP.'field_id='.$field['field_id'].'"><img src="'.$cp_theme_url.'images/content_custom_tab_delete.gif" alt="'.lang('delete').'" width="19" height="18" /></a>'
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_field_groups'), 'colspan' => 4));
			}
			
			echo $this->table->generate();
		?>
			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file category_management.php */
/* Location: ./themes/cp_themes/corporate/admin/category_management.php */