<?php if ( ! empty($filters) && is_array($filters)): ?>
<div class="title-bar__filter-toggle-button js-filter-bar-toggle" style="display: none">
	<button type="button" class="filter-bar__button button button--default button--small"><i class="fas fa-sm fa-filter"></i> <?=lang('filters')?></button>
</div>
<div class="filter-bar filter-bar--collapsible">
	<?php foreach ($filters as $filter): ?>
		<div class="filter-bar__item <?php if (!empty($filter['class'])) { echo $filter['class']; } ?>">
			<?=$filter['html']?>
		</div>
	<?php endforeach; ?>
	<?php if ($has_reset): ?>
		<div class="filter-bar__item">
			<a href="<?=$reset_url?>" class="filter-bar__button filter-bar__button--clear button button--default button--small"><i class="fas fa-minus-circle fa-sm"></i> <?=lang('clear_filters')?></a>
		</div>
	<?php endif;?>
	<button class="hidden"><?=lang('submit')?></button>
</div>
<?php endif; ?>
