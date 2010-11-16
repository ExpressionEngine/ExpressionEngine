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

		<div class="heading"><h2 class="edit"><?=lang($cp_page_title)?></h2></div>
		<?php $this->load->view('_shared/message');?>
		
		<div class="pageContents">

			<?=form_open('C=members'.AMP.'M=update_field_order')?>

			<?php
		
			if (count($fields) > 0)
			{		
				$this->table->set_template($cp_pad_table_template);
				$this->table->set_heading(
											lang('field_id'), 
											lang('fieldlabel'),
											lang('fieldname'),
											lang('edit_field_order')
										);

				foreach ($fields as $field)
				{
					$this->table->add_row(
											$field['id'],
											$field['label'],
											$field['name'],
											form_input($field)
										);
					
				}

				echo $this->table->generate();
			}

			?>
			
			<p><?=form_submit('', lang('update'), 'class="submit"')?></p>

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

/* End of file edit_custom_profile_field.php */
/* Location: ./themes/cp_themes/default/members/edit_custom_profile_field.php */