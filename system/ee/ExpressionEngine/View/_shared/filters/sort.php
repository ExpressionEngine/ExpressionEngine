<div class="filter-search-bar__item <?=($value ? 'in-use' : '')?>">
	<button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="<?=strtolower(lang($label))?>" title="<?=lang($label)?><?=($value ? strip_tags($value) : '')?>">
		<?=$value?>
	</button>

	<div class="dropdown">

		<div class="dropdown__scroll">
		<?php foreach ($options as $url => $label): ?>
			<a class="dropdown__link" href="<?=$url?>"><?=$label?></a>
		<?php endforeach; ?>
		</div>
	</div>
	<?php if ($value && isset($url_without_filter)): ?>
		<a class="filter-clear" href="<?=$url_without_filter?>"><span class="sr-only"><?=lang('clear_filter')?></span><i class="fal fa-times"></i></a>
	<?php endif; ?>
</div>
