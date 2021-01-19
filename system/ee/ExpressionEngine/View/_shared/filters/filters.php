<?php if (! empty($filters) && is_array($filters)): ?>
	<div class="filter-bar">
		<?php foreach ($filters as $filter): ?>
			<div class="filter-bar__item <?php if (!empty($filter['class'])) {
    echo $filter['class'];
} ?>">
				<?=$filter['html']?>
			</div>
		<?php endforeach; ?>
		<button class="hidden"><?=lang('submit')?></button>
	</div>
<?php endif; ?>
