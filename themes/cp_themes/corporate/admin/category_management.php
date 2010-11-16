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

		<div class="clear_left"></div>

		<?php
			$this->table->set_template($cp_table_template);
			$this->table->set_heading(
										lang('ID'),
										lang('category_group'),
										lang('add_edit_categories'),
										lang('edit_category_group'),
										lang('manage_custom_fields'),
										''
									);
									
			if (count($categories) > 0)
			{
				foreach ($categories as $group)
				{
					$this->table->add_row(
						'<strong>'.$group['group_id'].'</strong>',
						'<strong>'.$group['group_name'].'</strong>',
						'('.$group['category_count'].') <a href="'.BASE.AMP.'C=admin_content'.AMP.'M=category_editor'.AMP.'group_id='.$group['group_id'].'">'. lang('add_edit_categories').'</a>',
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=edit_category_group'.AMP.'group_id='.$group['group_id'].'">'.lang('edit_category_group').'</a>',
						'('.$group['custom_field_count'].') <a href="'.BASE.AMP.'C=admin_content'.AMP.'M=category_custom_field_group_manager'.AMP.'group_id='.$group['group_id'].'">'. lang('manage_custom_fields').'</a>',
						'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=category_group_delete_conf'.AMP.'group_id='.$group['group_id'].'">'.lang('delete_group').'</a>'
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_category_group_message'), 'colspan' => 6));
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

/* End of file category_management.php */
/* Location: ./themes/cp_themes/default/admin/category_management.php */