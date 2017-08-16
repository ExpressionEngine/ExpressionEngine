<li class="tbl-list-item">
	<div class="tbl-row">
		<div class="txt">
			<div class="main">
				<b><?=$page->title?></b>
			</div>
			<div class="secondary">
				<span class="faded">ID#</span> <?=$page->id?> <span class="faded">/</span> <?=$page->uri?>
			</div>
		</div>
		<ul class="toolbar">
			<li class="edit"><a href="<?=ee('CP/URL')->make('publish/edit/entry/' . $page->id)?>"></a></li>
		</ul>
		<div class="check-ctrl"><input type="checkbox" name="selection[]" value="<?=$page->id?>" data-confirm="<?=lang('page') . ': <b>' . $page->title . '</b>'?>"></div>
	</div>
	<?php if (count($page->children())): ?>
		<ul class="tbl-list">
			<?php foreach ($page->children() as $child): ?>
				<?php $this->embed('pages:_page', array('page' => $child)); ?>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</li>
