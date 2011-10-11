<?php if (isset($cp_action_nav) AND count($cp_action_nav)): ?>
	<ul id="action_nav">
		<?php foreach($cp_action_nav as $lang_key => $link): ?>
			<li class="button">
				<a title="<?=lang($lang_key)?>" class="submit <?=$lang_key?>" href="<?=$link?>"><?=lang($lang_key)?></a>
			</li>
		<?php endforeach; ?>
	</ul> <!-- action_nav -->
<?php endif; ?>