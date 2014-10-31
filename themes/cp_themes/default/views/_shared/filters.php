<?php if ( ! empty($filters) && is_array($filters)): ?>
<div class="filters">
	<b><?=lang('filters')?>: </b>
	<ul>
	<?php foreach ($filters as $filter): ?>
		<li><?=$filter?></li>
	<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>