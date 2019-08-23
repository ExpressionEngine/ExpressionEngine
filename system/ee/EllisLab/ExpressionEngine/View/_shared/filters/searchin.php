<a class="filter-bar__button has-sub js-dropdown-toggle" href="" data-filter-label="<?=strtolower(lang($label))?>">
	<?=lang($label)?>
	<?php if ($value): ?>
	<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
	<?php endif; ?>
</a>
<div class="dropdown">
	<ul>
	<?php foreach ($options as $url => $label): ?>
		<a class="dropdown__link" href="<?=$url?>"><?=$label?></a>
	<?php endforeach; ?>
	</ul>
</div>
