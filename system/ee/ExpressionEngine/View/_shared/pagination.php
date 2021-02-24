<?php if (! empty($pagination)): ?>
	<ul class="pagination">
		<?php if (array_keys($pagination['pages'])[0] != 1): ?>
		<li class="pagination__item"><a class="pagination__link" href="<?=$pagination['first']?>">1</a></li>
		<li class="pagination__item"><span class="pagination__divider">&hellip;</span></li>
		<?php endif;?>

		<?php foreach ($pagination['pages'] as $page => $link): ?>
		<li class="pagination__item <?php if ($pagination['current_page'] == $page): ?>pagination__item--active<?php endif; ?>"><a class="pagination__link" href="<?=$link?>"><?=$page?></a></li>
		<?php endforeach; ?>

		<?php if (array_reverse(array_keys($pagination['pages']))[0] != $pagination['total_pages']): ?>
		<li class="pagination__item"><span class="pagination__divider">&hellip;</span></li>
		<li class="pagination__item"><a class="pagination__link" href="<?=$pagination['last']?>"><?=$pagination['total_pages']?></a></li>
		<?php endif;?>

		<?php if (isset($pagination['per_page_selector']) && !empty($pagination['per_page_selector'])) : ?>
		<li class="pagination__item"><?=$pagination['per_page_selector']?></li>
		<?php endif;?>
	</ul>
<?php endif; ?>
