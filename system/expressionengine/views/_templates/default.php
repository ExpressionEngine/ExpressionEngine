<?php extend_template('wrapper', 'right_nav') ?>

<div class="contents">
	<?php $this->load->view('_shared/right_nav'); ?>
	<div class="heading">
		<h2 class="edit"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>
	</div>
	<div class="pageContents">
		<?php $this->load->view('_shared/message');?>
		<?=$EE_rendered_view?>
	</div>
</div>