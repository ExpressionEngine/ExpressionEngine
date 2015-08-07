<h2>
<?php if ($url): ?>
	<a href="<?=$url?>"><?=$text?></a>
<?php else: ?>
	<?=$text?>
<?php endif; ?>
<?php if (isset($button)): ?>
	<a class="btn action" href="<?=$button['url']?>"><?=$button['text']?></a>
<?php endif ?>
</h2>