<?php if ( ! empty($filters) && is_array($filters)): ?>
<div class="filter-bar">
	<?php foreach ($filters as $filter): ?>
		<div class="filter-bar__item <?php if (!empty($filter['class'])) { echo $filter['class']; } ?>">
			<?=$filter['html']?>
		</div>
	<?php endforeach; ?>
	<?php if ($has_reset): ?>
		<div class="filter-bar__item">
			<a href="<?=$reset_url?>" class="filter-bar__button filter-bar__button--clear"><i class="fas fa-minus-circle fa-sm"></i> <?=lang('clear_filters')?></a>
		</div>
	<?php endif;?>
</div>
<?php endif; ?>
