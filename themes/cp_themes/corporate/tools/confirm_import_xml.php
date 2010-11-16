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

			<div class="heading"><h2 class="edit"><?=lang('confirm_details')?></h2></div>
			<div class="pageContents">
				
			<?php if (count($added_fields) > 0):?>
				<p class="notice"><?=lang('new_fields_success')?></p>
				<p><?=implode('<br />', $added_fields)?></p>
			<?php endif;?>
	
			<?=form_open($post_url, '', $form_hidden)?>
	
			<p><?=lang('confirm_details_blurb')?></p>		

			<?php
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(
									lang('option'),
									lang('value')
								);

			foreach ($data_display as $type => $value)
			{
				$this->table->add_row(
										lang($type),
										$value
									);
			}
			?>

			<?=$this->table->generate()?>
			
			
			<p><?=lang('member_id_warning')?></p>		

			<p><?=form_submit('import_from_xml', lang('import'), 'class="submit"')?></p>

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

/* End of file confirm_import_xml.php */
/* Location: ./themes/cp_themes/default/tools/confirm_import_xml.php */