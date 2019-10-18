<h2 class="sidebar__section-title <?php if ($class) echo $class; ?>">
<?php if (isset($icon_class)): ?>
    <i class="fas fa-<?=$icon_class?>"></i>
<?php endif; ?>
<?php if ($url): ?>
	<a href="<?=$url?>"<?php if ($external) echo ' rel="external"'?>><?=$text?></a>
<?php else: ?>
	<?=$text?>
<?php endif; ?>
<?php if (isset($button)): ?>
	<a class="button button--small button--action" href="<?=$button['url']?>"><?=$button['text']?></a>
<?php endif ?>
</h2>
