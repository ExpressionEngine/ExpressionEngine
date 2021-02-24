<div class="filter-search-bar__item <?=($value ? 'in-use' : '')?>">
	<button type="button" class="filter-bar__button has-sub js-dropdown-toggle button button--default button--small" data-filter-label="<?=strtolower(lang($label))?>" title="<?=lang($label)?><?=($value ? ' (' . htmlentities($value, ENT_QUOTES, 'UTF-8') . ')' : '')?>">
		<?=lang($label)?>
		<?php if ($value): ?>
		<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
		<?php endif; ?>
	</button>
	<?php if ($value): ?>
		<a class="filter-clear" href="<?=$url_without_filter?>"><i class="fas fa-times"></i></a>
	<?php endif; ?>
	<div class="dropdown">
		<div class="dropdown__search">
			<div class="search-input">
			<input
				type="text"
				name="<?=$name?>"
				value="<?=htmlentities($custom_value, ENT_QUOTES, 'UTF-8')?>"
				placeholder="<?=htmlentities($placeholder, ENT_QUOTES, 'UTF-8')?>"
				rel="date-picker"
				class="search-input__input input--small"
				<?php if ($timestamp): ?>data-timestamp="<?=$timestamp?>" <?php endif; ?>
			>
			</div>
		</div>
		<div class="dropdown__scroll">
		<?php foreach ($options as $url => $label): ?>
			<a class="dropdown__link" href="<?=$url?>"><?=$label?></a>
		<?php endforeach; ?>
		</div>
	</div>
</div>