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

			<div class="heading"><h2><?=lang('member_import')?></h2></div>
			
			<div class="pageContents">
	
			<p><?=lang('member_import_welcome')?></p>
			
			<h3><a href="<?=BASE.AMP."C=tools_utilities".AMP."M=import_from_xml"?>"><?=lang('import_from_xml')?></a></h3>
			<p><?=lang('import_from_xml_blurb')?></p>
			
			<h3><a href="<?=BASE.AMP."C=tools_utilities".AMP."M=convert_from_delimited"?>"><?=lang('convert_from_delimited')?></a></h3>
			<p><?=lang('convert_from_delimited_blurb')?></p>		
	
			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file member_import.php */
/* Location: ./themes/cp_themes/corporate/tools/member_import.php */