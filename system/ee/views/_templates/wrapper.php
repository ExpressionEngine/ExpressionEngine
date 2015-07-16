<?php
$this->enabled('ee_header') && $this->view('_shared/header');
?>

	<?=$EE_rendered_view?>

<?php
$this->enabled('ee_footer') && $this->view('_shared/footer');
?>
