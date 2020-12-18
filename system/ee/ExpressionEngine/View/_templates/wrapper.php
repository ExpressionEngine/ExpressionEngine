<?php
$this->enabled('ee_header') && $this->embed('_shared/header');
?>

		<?= $child_view ?>

<?php
$this->enabled('ee_footer') && $this->embed('_shared/footer');
?>
