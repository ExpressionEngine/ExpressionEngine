<?php extend_template('wrapper', 'ee_right_nav') ?>

<div class="contents">
	<?php enabled('ee_right_nav') && $this->view('_shared/right_nav'); ?>

	<?php if (isset($cp_notice) && ! empty($cp_notice)):?>
		<div id="ee_important_message" class="<?=( ! $info_message_open) ? 'closed' : 'open'?>">
			<div class="contents" id="ee_homepage_notice">
				<div class="heading">
					<h2><span class="ee_notice_icon"></span><?=lang('important_messages')?><span class="msg_open_close">Ignore Button</span></h2>
				</div>
				<div class="pageContents open" id="noticeContents">
					<p id="newVersionNotice"><?=$cp_notice?></p>
					<div class="clear"></div>
				</div>
			</div>
		</div>
	<?php endif;?>

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