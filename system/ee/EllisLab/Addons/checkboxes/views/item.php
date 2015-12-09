<?php
	foreach ($options as $key => $value):

		$children = NULL;

		// If the value is an array, then we have children. Add them to the
		// queue with depth markers and set the real value to render the parent.
		if (is_array($value))
		{
			$children = $value['children'];
			$value = $value['name'];
		}

		$checked = (in_array($value, $values) OR in_array(form_prep($value), $values));

		$class = 'choice block';

		if ($checked)
		{
			$class .= ' chosen';
		}
?>
	<li<?php if ($editable): ?> class="nestable-item" data-id="<?=$key?>"<?php endif ?>>
		<label class="<?=$class?>">
			<?php if ($editable): ?>
				<span class="list-reorder"></span>
			<?php endif ?>
			<?=form_checkbox($field_name.'[]', $key, $checked)?> <?=$value?>
			<?php if ($editable OR $deletable): ?>
				<ul class="toolbar">
					<?php if ($editable): ?>
						<li class="edit"><a class="m-link" rel="modal-category-form" data-cat-group="<?=$cat_group_id?>" data-cat-id="<?=$key?>" href=""></a></li>
					<?php endif ?>
					<?php if ($deletable): ?>
						<li class="remove"><a class="m-link" rel="modal-confirm-cat-remove" data-confirm="<?='<b>'.lang('category').'</b>: '.$value?>" data-cat-id="<?=$key?>" href=""></a></li>
					<?php endif ?>
				</ul>
			<?php endif ?>
		</label>
<?php
	if (isset($children)):
?>
		<ul<?php if ($editable): ?> class="nestable-list"<?php endif ?>>
			<?php $this->embed('item', array('options' => $children, 'values' => $values)); ?>
		</ul>
<?php
	endif;
?>
	</li>
<?php
endforeach;
?>
