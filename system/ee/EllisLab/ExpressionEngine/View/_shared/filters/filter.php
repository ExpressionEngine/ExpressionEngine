<a class="has-sub" href="" data-filter-label="<?=strtolower(lang($label))?>">
	<?=strtolower(lang($label))?>
	<?php if ($value): ?>
	<span class="faded">(<?=$value?>)</span>
	<?php endif; ?>
</a>
<div class="sub-menu">
	<?php if ($has_custom_value): ?>
	<fieldset class="filter-search">
		<input
			type="text"
			name="<?=$name?>"
			value="<?=$custom_value?>"
			placeholder="<?=$placeholder?>"
		>
	</fieldset>
	<?php endif; ?>
	<?php if (count($options) > 10): ?><div class="scroll-wrap"><?php endif;?>
	<ul>
	<?php foreach ($options as $url => $label): ?>
		<li><a href="<?=$url?>"><?=$label?></a></li>
	<?php endforeach; ?>
	</ul>
	<?php if (count($options) > 10): ?></div><?php endif;?>
</div>
