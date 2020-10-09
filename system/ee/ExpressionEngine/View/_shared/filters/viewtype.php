<div class="">
	<?php foreach ($options as $option => $info): ?>
	<?php if ($value != $option): ?>
		<a class="filter-bar__button button button--default button--small" href="<?=$info['url']?>" title="<?=lang('view_as') . $info['label']?>">
			<?php if ($option == 'list'): ?>
				<i class="fas fa-fw fa-list"></i>
			<?php else: ?>
				<i class="fas fa-fw fa-grip-horizontal"></i>
			<?php endif; ?>
		</a>
	<?php endif; ?>
	<?php endforeach; ?>
</div>
