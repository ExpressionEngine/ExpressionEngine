<?php if ( ! empty($filters) && is_array($filters)): ?>
<div class="filters">
	<b><?=lang('filters')?>: </b>
	<ul>
	<?php foreach ($filters as $filter): ?>
		<li><?=$filter?></li>
	<?php endforeach; ?>
	<?php if ($has_reset): ?>
		<li class="filter-clear"><a href="<?=$reset_url?>"><?=lang('clear_filters')?></a></li>
	<?php endif;?>
	</ul>
</div>
<?php endif; ?>
