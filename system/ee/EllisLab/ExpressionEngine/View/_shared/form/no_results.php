<div class="no-results">
	<p><?=lang($text)?></p>
	<?php if (isset($link_href)): ?>
		<p><a class="btn action" href="<?=$link_href?>">
			<?=lang($link_text)?>
		</a></p>
	<?php endif ?>
</div>
