<li class="js-nested-item" data-id="<?=$category->data->cat_id?>">
	<div class="list-item list-item--action <?php if (ee()->session->flashdata('highlight_id') == $category->data->cat_id): ?> list-item--selected<?php endif ?>" style="position: relative;">
		<?php if ($can_edit_categories): ?>
		<div class="list-item__handle"><i class="fal fa-bars"></i></div>
		<?php endif; ?>
    <div class="list-item__secondary" style="left: 51px;">
      #<?=$category->data->cat_id?> <span class="faded">/</span> <span class="click-select-text"><?=$category->data->cat_url_title?></span>
    </div>
		<a class="list-item__content" <?php if ($can_edit_categories): ?>href="<?=ee('CP/URL')->make('categories/edit/' . $category->data->group_id . '/' . $category->data->cat_id)?>"<?php endif; ?>>
			<div class="list-item__title">
				<?=ee('Format')->make('Text', $category->data->cat_name)->convertToEntities()?>
			</div>
			<div class="list-item__secondary">&#160;</div>
		</a>
		<?php if ($can_delete_categories): ?>
		<div class="list-item__checkbox">
			<label class="hidden" for="cat_cb_<?=$category->data->cat_id?>"><?=lang('select') . ' ' . $category->data->cat_name?></label>
			<input id="cat_cb_<?=$category->data->cat_id?>" type="checkbox" name="categories[]" value="<?=$category->data->cat_id?>" data-confirm="<?=lang('category') . ': <b>' . htmlentities($category->data->cat_name, ENT_QUOTES, 'UTF-8') . '</b>'?>">
		</div>
		<?php endif; ?>
	</div>
	<?php if (count($category->children())): ?>
		<ul class="list-group list-group--nested">
			<?php foreach ($category->children() as $child): ?>
				<?php $this->embed('channels/cat/_category', array('category' => $child)); ?>
			<?php endforeach ?>
		</ul>
	<?php endif ?>
</li>
