<?php
	foreach ($options as $item):

		$key = $item->getId();
		$value = $item->name;
		$children = $item->Children->sortBy('sort');

		$class = 'choice block';
		$extra = '';
?>
	<li data-id="<?=$key?>" class="nestable-item">
		<label class="<?=$class?>">
			<span class="list-reorder"></span>
			<?=htmlentities($value)?>
			<input type="hidden" name="sort[]" value="<?=$key?>" />
			<ul class="toolbar">
				<li class="edit"><a class="m-link" rel="modal-menu-edit" data-group-id="<?=$set_id?>" data-content-id="<?=$key?>" href=""></a></li>
				<li class="remove"><a class="m-link" rel="modal-menu-confirm-remove" data-confirm="<?=form_prep('<b>'.$content_item_label.'</b>: '.htmlentities($value))?>" data-content-id="<?=$key?>" href=""></a></li>
			</ul>
		</label>
<?php
	if (count($children)):
?>
		<ul class="nested-list">
			<?php $this->embed('settings/menu-manager/item', array('options' => $children)); ?>
		</ul>
<?php
	endif;
?>
	</li>
<?php
endforeach;
?>
