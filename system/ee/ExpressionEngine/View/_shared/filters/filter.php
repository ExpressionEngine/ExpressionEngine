<div class="filter-search-bar__item <?=($value ? 'in-use' : '')?>">
	<button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="<?=strtolower(lang($label))?>" title="<?=lang($label)?><?=($value ? ' (' . htmlentities($value, ENT_QUOTES, 'UTF-8') . ')' : '')?>">
		<?=lang($label)?>
		<?php if ($value): ?>
		<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
		<?php endif; ?>
	</button>

	<div class="dropdown">
		<?php if ($has_custom_value || $has_list_filter): ?>
			<div class="dropdown__search">
				<div class="search-input">
				<label for="<?=$name?>" class="sr-only"><?=$name?></label>
				<input
					type="text"
					name="<?=$name?>"
					value="<?=htmlentities($custom_value, ENT_QUOTES, 'UTF-8')?>"
					placeholder="<?=htmlentities($placeholder, ENT_QUOTES, 'UTF-8')?>"
					<?php if ($has_list_filter): ?>
					data-fuzzy-filter="true"
					<?php endif; ?>
					class="search-input__input input--small"
					id="<?=$name?>"
				>
				</div>
			</div>
		<?php endif; ?>

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
