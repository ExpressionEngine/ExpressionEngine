<?php
if ($EE_view_disable !== TRUE)
{
	enabled('ee_header') && $this->view('_shared/header');
}
?>

	<?=$EE_rendered_view?>

<?php
if ($EE_view_disable !== TRUE)
{
	enabled('ee_footer') && $this->view('_shared/footer');
}
?>