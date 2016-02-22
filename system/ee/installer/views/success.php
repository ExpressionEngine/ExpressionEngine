<div class="alert inline success">
	<h3><?=$success_note?></h3>
	<p><?=lang('success_delete')?></p>
</div>
<fieldset class="install-btn">
	<a class="btn" href="<?=$cp_login_url?>"><?=lang('cp_login')?></a>
	<?php if ($mailing_list): ?>
		<input class="btn action" type="submit" name="download" value="<?=lang('download_mailing_list')?>">
	<?php endif; ?>
</fieldset>
