<div class="button-group">
	<?php
	$pending_count = ee('Model')->get('Member')->filter('role_id', 4)->count();

	if ($pending_count > 0):
	?>
	<a href="<?=ee('CP/URL')->make('members/pending')?>" class="button button--default button--small"><?=$pending_count?> <?=lang('pending')?></a>
	<?php endif; ?>

	<a class="button button--default button--small" href="<?=ee('CP/URL', 'members')?>"><?=lang('view_all')?></a>
</div>
