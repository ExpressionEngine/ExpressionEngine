<a class="has-sub" href="">
	<?=strtolower(lang($label))?>
	<?php if ($value): ?>
	<span class="faded">(<?=$value?>)</span>
	<?php endif; ?>
</a>
<div class="sub-menu">
	<fieldset class="filter-search">
		<input
			type="text"
			name="<?=$name?>"
			value="<?=$custom_value?>"
			placeholder="<?=$placeholder?>"
			rel="date-picker"
			<?php if ($timestamp): ?>data-timestamp="<?=$timestamp?>" <?php endif; ?>
		>
	</fieldset>
	<ul>
	<?php foreach ($options as $url => $label): ?>
		<li><a href="<?=$url?>"><?=$label?></a></li>
	<?php endforeach; ?>
	</ul>
</div>