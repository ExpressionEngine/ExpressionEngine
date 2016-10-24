<a class="has-sub" href="" data-filter-label="<?=strtolower(lang($label))?>">
	<?=strtolower(lang($label))?>
	<?php if ($value): ?>
	<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
	<?php endif; ?>
</a>
<div class="sub-menu">
	<?php if ($has_custom_value || $has_list_filter): ?>
	<fieldset class="filter-search">
		<input
			type="text"
			name="<?=$name?>"
			value="<?=htmlentities($custom_value, ENT_QUOTES, 'UTF-8')?>"
			placeholder="<?=htmlentities($placeholder, ENT_QUOTES, 'UTF-8')?>"
			<?php if ($has_list_filter): ?>
			autofocus="autofocus"
			data-fuzzy-filter="true"
			<?php endif; ?>
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
