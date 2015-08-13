<h2<?php if ($class) echo ' class="' . $class . '"'?>>
<?php if ($url): ?>
	<a href="<?=$url?>"><?=$text?></a>
<?php else: ?>
	<?=$text?>
<?php endif; ?>
<?php if (isset($button)): ?>
	<a class="btn action" href="<?=$button['url']?>"<?php if ($external) echo ' rel="external"'?>><?=$button['text']?></a>
<?php endif ?>
</h2>