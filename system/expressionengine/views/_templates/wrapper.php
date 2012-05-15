<?php
if ($EE_view_disable !== TRUE)
{
	enabled('ee_header') && $this->view('_shared/header');
	enabled('ee_menu') && $this->view('_shared/main_menu');
	enabled('ee_sidebar') && $this->view('_shared/sidebar');
	enabled('ee_breadcrumbs') && $this->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>

	<?php enabled('ee_right_nav') && $this->view('_shared/right_nav'); ?>
	<?=$EE_rendered_view?>

</div>

<div class="shun">&nbsp;</div>

<?php
if ($EE_view_disable !== TRUE)
{
	enabled('ee_accessories') && $this->view('_shared/accessories');
	enabled('ee_footer') && $this->view('_shared/footer');
}
?>