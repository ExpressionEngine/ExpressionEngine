
<?php if (count($cp_right_nav)): ?>
<ul>
	<?php foreach($cp_right_nav as $lang_key => $link): ?>
		<li><a title="<?=lang($lang_key)?>" class="submit" href="<?=$link?>"><?=lang($lang_key)?></a></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>