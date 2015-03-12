<?php if (count($cp_right_nav)): ?>
<div class="rightNav">
	<div style="float: left; width: 100%;">
	<?php foreach($cp_right_nav as $lang_key => $link): ?>
		<span class="button"><a title="<?=lang($lang_key)?>" class="submit" href="<?=$link?>"<?php if ($lang_key == "view_rendered_template"): ?>rel="external"<?php endif; ?>><?=lang($lang_key)?></a></span>
	<?php endforeach; ?>
	</div>
	<div class="clear_left"></div>
</div>
<?php endif; ?>