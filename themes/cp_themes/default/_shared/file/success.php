<?php $this->load->view('_shared/file/iframe_header'); ?>

<h2><?= $success ?></h2>

<script>
	parent.$.ee_fileuploader.clean_up($.parseJSON('<?= $file ?>'));
</script>
	
<?php $this->load->view('_shared/file/iframe_footer') ?>