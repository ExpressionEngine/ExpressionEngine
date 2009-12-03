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
		<?php $this->load->view('_shared/message')?>

		<div class="heading">
			<h2><span id="filter_ajax_indicator"><img src="<?=$cp_theme_url?>images/indicator2.gif" style="padding-right:20px;" /></span><?=$cp_page_title?></h2>
		</div>		
		

		<div class="pageContents moduleWrap">

		<?=$_module_cp_body?>

		</div> <!-- pageContents -->
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file module_cp_container.php */
/* Location: ./themes/cp_themes/corporate/modules/module_cp_container.php */