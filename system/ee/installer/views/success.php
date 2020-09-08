<div class="box <?=empty($update_notices) ? 'success' : 'warn'?>">
	<h1><?=$title?><span class="icon-success"></span></h1>
	<div class="updater-msg">
		<p><?=$success_note?></p>
		<?php if ( ! empty($update_notices)): ?>
			<?php foreach ($update_notices as $notice): ?>
				<div class="alert-notice">
					<?php if ($notice->is_header): ?>
						<p><b><?=$notice->message?></b></p>
					<?php else: ?>
						<p><?=$notice->message?></p>
					<?php endif?>
				</div>
			<?php endforeach;?>
		<?php endif ?>
		<p class="msg-choices">
			<a href="<?=$cp_login_url?>"><?=lang('cp_login')?></a>
			<?php if ($mailing_list): ?>
				<a href="<?=$action?>&download"><?=lang('download_mailing_list')?></a>
			<?php endif; ?>
		</p>
	</div>
</div>
