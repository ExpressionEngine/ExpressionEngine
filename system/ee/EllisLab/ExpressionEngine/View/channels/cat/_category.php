<li class="tbl-list-item" data-id="<?=$category->data->cat_id?>">
	<div class="tbl-row<?php if (ee()->session->flashdata('highlight_id') == $category->data->cat_id): ?> selected<?php endif ?>">
		<?php if ($can_edit_categories): ?>
		<div class="reorder"></div>
		<?php endif; ?>
		<div class="txt">
			<div class="main">
				<b><?=ee('Format')->make('Text', $category->data->cat_name)->convertToEntities()?></b>
			</div>
			<div class="secondary">
				<span class="faded">ID#</span> <?=$category->data->cat_id?> <span class="faded">/</span> <?=$category->data->cat_url_title?>
			</div>
		</div>
		<?php if ($can_edit_categories): ?>
		<ul class="toolbar">
			<li class="edit"><a href="<?=ee('CP/URL')->make('categories/edit/'.$category->data->group_id.'/'.$category->data->cat_id)?>"></a></li>
		</ul>
		<?php endif; ?>
		<?php if ($can_delete_categories): ?>
		<div class="check-ctrl"><input type="checkbox" name="categories[]" value="<?=$category->data->cat_id?>" data-confirm="<?=lang('category') . ': <b>' . htmlentities($category->data->cat_name, ENT_QUOTES, 'UTF-8') . '</b>'?>"></div>
		<?php endif; ?>
	</div>
	<?php if (count($category->children())): ?>
		<ul class="tbl-list">
			<?php foreach ($category->children() as $child): ?>
				<?php $this->embed('channels/cat/_category', array('category' => $child)); ?>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</li>
