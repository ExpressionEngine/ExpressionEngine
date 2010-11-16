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
<div class="heading"><h2><?=lang('import_utilities')?></h2></div>
		<div class="pageContents">
			
			<ul class="bullets">
					<li><a href="<?=BASE.AMP."C=tools_utilities".AMP."M=member_import"?>"><?=lang('member_import')?></a></li>
			</ul>
		</div>


	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file import_utilities.php */
/* Location: ./themes/cp_themes/default/tools/import_utilities.php */