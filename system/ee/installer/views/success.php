<div class="alert inline success">
	<h3><?=$success_note?></h3>
	<p><?=lang('success_delete')?></p>
</div>

<?php if (count($update_notices)): ?>
<h2>Update Notices</h2>
	<?php foreach ($update_notices as $notice): ?>
		<?php if ($notice->is_header): ?>
			<h4><?=$notice->message?></h4>
		<?php else: ?>
			<p><?=$notice->message?></p>
		<?php endif?>
	<?php endforeach;?>
<?php endif;?>

<fieldset class="install-btn">
	<a class="btn" href="<?=$cp_login_url?>"><?=lang('cp_login')?></a>
	<?php if ($mailing_list): ?>
		<input class="btn action" type="submit" name="download" value="<?=lang('download_mailing_list')?>">
	<?php endif; ?>
</fieldset>
