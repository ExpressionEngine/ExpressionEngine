<div class="author-info-comments">
	<?php if ($comment->author_id && !is_null($comment->Author)):
		if (!ee('Permission')->isSuperAdmin()) {
			$can_operate_member = (bool) (ee('Permission')->can('edit_members') && $member->PrimaryRole->is_locked != 'y');
		} else {
			$can_operate_member = true;
		}
		$avatar_url = ($comment->Author->avatar_filename) ? ee()->config->slash_item('avatar_url') . $comment->Author->avatar_filename : (URL_THEMES . 'asset/img/default-avatar.png');
	?>

	<img src="<?=$avatar_url?>" alt="<?=$comment->Author->screen_name?>" class="avatar-icon">
	<?php endif; ?>
	<div class="author-details">
		<?php if (isset($can_operate_member) && $can_operate_member): ?>
			<a href="<?=ee('CP/URL')->make('members/profile', ['id' => $comment->author_id])?>"><?=$comment->name?></a><br>
		<?php else: ?>
			<?=$comment->name?><br>
		<?php endif; ?>
		<?php if ($comment->email): ?>
			<span class="meta-info"><a class="text-muted" href="mailto:<?=$comment->email?>"><?=$comment->email?></a></span><br>
		<?php endif; ?>
		<?php if (!empty($comment->url)) : ?>
			<span class="meta-info"><a class="text-muted" href="<?=ee()->cp->masked_url($comment->url)?>"><?=$comment->url?></a></span><br>
		<?php endif; ?>
		<?php if (!empty($comment->location)) : ?>
			<span class="meta-info"><?=$comment->location?></span><br>
		<?php endif; ?>
		<span class="meta-info"><?=lang('ip_address')?>: <?=$comment->ip_address?></span>
	</div>
</div>
