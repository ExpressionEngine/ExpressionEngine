<?php if ( ! empty($filters) && is_array($filters)): ?>
<div class="filter-bar">
	<?php foreach ($filters as $filter): ?>
		<div class="filter-bar__item <?php if (!empty($filter['class'])) { echo $filter['class']; } ?>">
			<?=$filter['html']?>
		</div>
	<?php endforeach; ?>
	<?php if ($has_reset): ?>
		<div class="filter-clear"><a href="<?=$reset_url?>"><?=lang('clear_filters')?></a></div>
	<?php endif;?>
</div>
<?php endif; ?>
