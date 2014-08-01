<h2><?=lang('running_current')?></h2>

<div class="shade">

<p class="important"><?=lang('delete_via_ftp')?></p>

<p class="pad"><?=lang('folder_is_located_at')?> <em><?=$installer_path?></em></p>

<p class="important"><strong><?=lang('no_access_until_delete')?></strong></p>

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

<h2><?=lang('bookmark_links')?></h2>

<p><a href="<?=$cp_url?>" target="_blank"><?=lang('cp_located_here')?></a></p>

<p><a href="<?=$site_url?>" target="_blank"><?=lang('site_located_here')?></a></p>