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
			<?=form_open('C=admin_content'.AMP.'M=update_category_group', '', $form_hidden)?>
		<?php
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
									lang('preference'),
									lang('setting')
								);
								
		$this->table->add_row(array(
				lang('name_of_category_group', 'group_name'),
				form_input(array('id'=>'group_name','name'=>'group_name','class'=>'field', 'value'=>$group_name))
			)
		);
		
		$this->table->add_row(array(
				lang('cat_field_html_formatting', 'field_html_formatting'),
				form_dropdown('field_html_formatting', $formatting_options, $field_html_formatting)
			)
		);
		
		$setting = '';
		
		if (count($can_edit_checks) == 0)
		{
			$setting = '<br /><span class="notice">'.str_replace('%x', strtolower(lang('edit')), lang('no_member_groups_available')).'<a class="less_important_link" title="'.lang('member_groups').'" href="'.BASE.AMP.'C=members'.AMP.'M=member_group_manager">'.lang('member_groups').'</a></span>';
		}
		else
		{
			foreach($can_edit_checks as $check)
			{
				$setting .= '<br /><label>'.form_checkbox('can_edit_categories[]', $check['id'], $check['checked']).' '.$check['value'].'</label>';
			}
		}
		
		$this->table->add_row(array(
				lang('can_edit_categories', 'can_edit_categories'),
				$setting
			)
		);
		
		$setting = '';

		if (count($can_delete_checks) == 0)
		{
			$setting .= '<br /><span class="notice">'.str_replace('%x', strtolower(lang('delete')), lang('no_member_groups_available')).
					   '<a class="less_important_link" title="'.lang('member_groups').'" href="'.BASE.AMP.'C=members'.AMP.'M=member_group_manager">'.lang('member_groups').'</a></span>';
		}
		else 
		{
			foreach ($can_delete_checks as $check)
			{
				$setting .= '<br /><label>'.form_checkbox('can_delete_categories[]', $check['id'], $check['checked']).' '.$check['value'].'</label>';
			}
		}
		
		$this->table->add_row(array(
				lang('can_delete_categories', 'can_delete_categories'),
				$setting
			)
		);
								
		echo $this->table->generate();
		?>

			<?=form_submit('submit', lang('submit'), 'class="submit"')?>

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

/* End of file field_group_create.php */
/* Location: ./themes/cp_themes/default/admin/field_group_create.php */