<?php extend_template('wrapper', 'ee_right_nav') ?>

<div class="contents">
	<?php enabled('ee_right_nav') && $this->view('_shared/right_nav'); ?>
	<div class="heading">
		<h2 class="edit">
			<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
			<?php enabled('ee_action_nav') && $this->view('_shared/action_nav') ?>
		</h2>
	</div>
	<div class="pageContents group">
		<?php enabled('ee_message') && $this->view('_shared/message');?>
		<?=$EE_rendered_view?>
	</div>
</div>