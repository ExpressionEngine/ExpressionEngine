<?php extend_template('default') ?>

<p class="go_notice">
	<?php if (isset($total_sent) AND $total_sent > 1): ?>
	<?=lang('all_email_sent_message')?>
	<?php else: ?>
	<?=lang('email_sent_message')?>
	<?php endif; ?>
</p>

<?php if (isset($total_sent)): ?>
<p class="go_notice"><?=lang('total_emails_sent')?> <?=$total_sent?></p>
<?php endif; ?>

<?=$debug?><br />
