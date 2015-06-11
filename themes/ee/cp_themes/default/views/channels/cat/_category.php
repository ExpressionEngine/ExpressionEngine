<li class="tbl-list-item" data-id="<?=$category->data->cat_id?>">
	<div class="tbl-row">
		<div class="reorder"></div>
		<div class="txt">
			<div class="main">
				<b><?=$category->data->cat_name?></b>
			</div>
			<div class="secondary">
				<span class="faded">ID#</span> <?=$category->data->cat_id?> <span class="faded">/</span> <?=$category->data->cat_url_title?>
			</div>
		</div>
		<ul class="toolbar">
			<li class="edit"><a href="<?=cp_url('channels/cat/edit-cat/'.$category->data->cat_id)?>"></a></li>
		</ul>
		<div class="check-ctrl"><input type="checkbox" name="categories[]" value="<?=$category->data->cat_id?>" data-confirm="<?=lang('category') . ': <b>' . htmlentities($category->data->cat_name, ENT_QUOTES) . '</b>'?>"></div>
	</div>
	<?php if (count($category->children())): ?>
		<ul class="tbl-list">
			<?php foreach ($category->children() as $child): ?>
				<?php $this->view('channel/cat/_category', array('category' => $child)); ?>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</li>