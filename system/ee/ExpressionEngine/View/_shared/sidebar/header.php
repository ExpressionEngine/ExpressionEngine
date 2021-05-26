<h2 class="sidebar__section-title <?php if ($class) {
    echo $class;
} ?>">
<?php if ($url): ?>
	<a href="<?=$url?>"<?php if ($external) {
    echo ' rel="external"';
}?>><?=$text?></a>
<?php else: ?>
	<?=$text?>
<?php endif; ?>
<?php if (isset($button)): ?>
	<a class="button button--xsmall button--primary" href="<?=$button['url']?>"><?=$button['text']?></a>
<?php endif ?>
</h2>
