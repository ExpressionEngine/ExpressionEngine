<button type="button" class="has-sub filter-bar__button js-dropdown-toggle" data-filter-label="<?=strtolower(lang($label))?>">
	<?=lang($label)?>
	<?php if ($value): ?>
	<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
	<?php endif; ?>
</button>
<div class="dropdown">
	<?php if ($has_custom_value || $has_list_filter): ?>
	<div class="dropdown__search">
		<div class="search-input">
		<input
			type="text"
			name="<?=$name?>"
			value="<?=htmlentities($custom_value, ENT_QUOTES, 'UTF-8')?>"
			placeholder="<?=htmlentities($placeholder, ENT_QUOTES, 'UTF-8')?>"
			<?php if ($has_list_filter): ?>
			data-fuzzy-filter="true"
			<?php endif; ?>
			class="search-input__input"
		>
		</div>
	</div>
	<?php endif; ?>
	<?php foreach ($options as $url => $label): ?>
		<a class="dropdown__link" href="<?=$url?>"><?=$label?></a>
	<?php endforeach; ?>
</div>
