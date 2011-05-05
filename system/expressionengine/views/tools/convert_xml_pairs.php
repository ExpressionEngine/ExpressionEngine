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

			<div class="heading"><h2 class="edit"><?=lang('assign_fields')?></h2></div>
			<div class="pageContents">
				
			<?=validation_errors(); ?>
	
			<?=form_open('C=tools_utilities'.AMP.'M=confirm_data_form', '', $form_hidden)?>
	
			<p><?=lang('assign_fields_blurb')?></p>		
			<p class="alert"><?=lang('password_field_warning')?></p>	


			<p><?=lang('required_fields')?></p>
			<?php
			$heading[] = lang('your_data');
			$heading[] = lang('member_fields');
			
			if (count($custom_select_options) > 1)
			{
				$heading[] = lang('custom_member_fields');
			}
			
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading($heading);

			$i=0;
			foreach ($fields[0] as $key => $value)
			{
				if (count($custom_select_options) > 1)
					{
						$this->table->add_row(											
											$value,
											form_dropdown('field_'.$i, $select_options),
											form_dropdown('c_field_'.$i, $custom_select_options)
									);
					}
					else
					{
						$this->table->add_row(	
											$value,
											form_dropdown('field_'.$i, $select_options)										
									);
					}

				$i++;
			}
			?>

			<?=$this->table->generate()?>
			

			<p class="field_format_option select_format">
					<?=form_checkbox('encrypt', 'y', set_checkbox('encrypt', 'y', $encrypt))?>
					<?=lang('plaintext_passwords', 'encrypt')?><br />

				</p>

			<p><?=form_submit('map', lang('map_elements'), 'class="submit"')?></p>

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

/* End of file convert_xml_pairs.php */
/* Location: ./themes/cp_themes/default/tools/convert_xml_pairs.php */