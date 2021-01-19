<?php if (! empty($filters) && is_array($filters)): ?>
	<?php foreach ($filters as $filter): ?>
		<?=$filter['html']?>
	<?php endforeach; ?>
	<button class="hidden"><?=lang('submit')?></button>
<?php endif; ?>
