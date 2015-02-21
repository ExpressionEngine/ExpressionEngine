<li class="tbl-list-item" data-id="<?=$category->cat_id?>">
	<div class="tbl-row">
		<div class="reorder"></div>
		<div class="txt">
			<div class="main">
				<b><?=$category->cat_name?></b>
			</div>
			<div class="secondary">
				<span class="faded">ID#</span> <?=$category->cat_id?> <span class="faded">/</span> <?=$category->cat_url_title?>
			</div>
		</div>
		<ul class="toolbar">
			<li class="edit"><a href="<?=cp_url('channel/cat/edit-cat/'.$category->cat_id)?>"></a></li>
		</ul>
		<div class="check-ctrl"><input type="checkbox"></div>
	</div>
	<?php $children = $category->getChildren();
	if (count($children)): ?>
		<ul class="tbl-list">
			<?php foreach ($children as $child): ?>
				<?php $this->view('channel/cat/_category', array('category' => $child)); ?>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</li>