<li>
	<div class="list-item list-item--action">
		<a href="<?=ee('CP/URL')->make('publish/edit/entry/' . $page->id)?>" class="list-item__content">
			<div class="list-item__title">
				<?=$page->title?>
			</div>
			<div class="list-item__secondary">
				#<?=$page->id?> <span class="faded">/</span> <?=$page->uri?>
			</div>
		</a>
		<div class="list-item__checkbox"><input type="checkbox" name="selection[]" value="<?=$page->id?>" data-confirm="<?=lang('page') . ': <b>' . $page->title . '</b>'?>"></div>
	</div>
	<?php if (count($page->children())): ?>
		<ul>
			<?php foreach ($page->children() as $child): ?>
				<?php $this->embed('pages:_page', array('page' => $child)); ?>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</li>
