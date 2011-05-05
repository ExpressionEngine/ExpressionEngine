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
		<?php $this->load->view('_shared/message');?>

	<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
    <div class="pageContents">
		<?=$message?>
	</div>
</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file message.php */
/* Location: ./themes/cp_themes/default/members/message.php */