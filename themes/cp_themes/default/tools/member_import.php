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

			<div class="heading"><h2 class="edit"><?=lang('member_import')?></h2></div>
			<div class="pageContents">
	
			<h4 style="margin-bottom:10px"><?=lang('member_import_welcome')?></h4>
			
			<?php 

			$this->table->set_template($cp_pad_table_template);
			$this->table->add_row(array(
					lang('import_from_xml').' '.lang('import_from_xml_blurb'),
					'<a title="'.lang('import_from_xml').'" href="'.BASE.AMP.'C=tools_utilities'.AMP.'M=import_from_xml">'.lang('import_from_xml').'</a>'					
				)
			);

			$this->table->add_row(array(
					lang('convert_from_delimited').' '.lang('convert_from_delimited_blurb'),
					'<a title="'.lang('convert_from_delimited').'" href="'.BASE.AMP.'C=tools_utilities'.AMP.'M=convert_from_delimited">'.lang('convert_from_delimited').'</a>'
				)
			);



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

/* End of file member_import.php */
/* Location: ./themes/cp_themes/default/tools/member_import.php */