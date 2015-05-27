<ul class="arrow-list">
	<li><?=$comment->name?> <?php if ($comment->author_id): ?><span class="reg-member" title="registered member"></span><?php endif; ?></li>
	<?php if ($comment->email): ?>
	<li><a href="mailto:<?=$comment->email?>"><?=$comment->email?></a></li>
	<?php endif; ?>
	<li><?=$comment->location?></li>
	<li><?=lang('ip_address')?>: <?=$comment->ip_address?></li>
</ul>
