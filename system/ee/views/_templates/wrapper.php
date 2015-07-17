<?php
$this->enabled('ee_header') && $this->embed('_shared/header');
?>

	<?=$EE_rendered_view?>

<?php
$this->enabled('ee_footer') && $this->embed('_shared/footer');
?>
