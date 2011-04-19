<?php $this->load->view('_shared/file/iframe_header'); ?>

<h2><?= $success ?></h2>
<script>
	var file = $.parseJSON('<?= $file ?>');
	parent.$.ee_fileuploader.place_file(file);
</script>

<?php $this->load->view('_shared/file/iframe_footer') ?>