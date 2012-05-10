<?php
if ($EE_view_disable !== TRUE)
{
	enabled('header') && $this->load->view('_shared/header');
	enabled('menu') && $this->load->view('_shared/main_menu');
	enabled('sidebar') && $this->load->view('_shared/sidebar');
	enabled('breadcrumbs') && $this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>

	<?php enabled('right_nav') && $this->load->view('_shared/right_nav'); ?>
	<?=$EE_rendered_view?>

</div>

<div class="shun">&nbsp;</div>

<?php
if ($EE_view_disable !== TRUE)
{
	enabled('accessories') && $this->load->view('_shared/accessories');
	enabled('footer') && $this->load->view('_shared/footer');
}
?>