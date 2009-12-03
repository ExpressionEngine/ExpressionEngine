<?php if (count($cp_right_nav)): ?>
<div class="rightNav shun">
	<div style="float: left; width: 100%;">
	<?php foreach($cp_right_nav as $lang_key => $link): ?>
		<a title="<?=lang($lang_key)?>" class="blueButton" href="<?=$link?>"><?=lang($lang_key)?></a>
	<?php endforeach; ?>
	</div>
	<div class="clear_left"></div>
</div>
<?php endif; ?>