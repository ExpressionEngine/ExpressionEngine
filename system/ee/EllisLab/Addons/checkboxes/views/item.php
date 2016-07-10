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

		$checked = (in_array($key, $values) OR in_array(form_prep($key), $values));

		$class = 'choice block';

		if ($checked)
		{
			$class .= ' chosen';
		}

		$extra = trim(implode(' ', array($editing, $disabled)));
?>
	<li<?php if ($editable): ?> class="nestable-item" data-id="<?=$key?>"<?php endif ?> style="overflow:hidden">
		<label class="<?=$class?>">
			<?php if ($editable): ?>
				<span class="list-reorder" <?php if ( ! $editing): ?>style="margin-left:-50px"<?php endif ?>></span>
			<?php endif ?>
			<?=form_checkbox($field_name.'[]', $key, $checked, $extra ? 'disabled="disabled"' : '')?> <?=htmlentities($value)?>
			<?php if ($editable OR $deletable): ?>
				<ul class="toolbar<?php if ( ! $editing): ?> hidden<?php endif ?>">
					<?php if ($editable): ?>
						<li class="edit"><a class="m-link" rel="modal-checkboxes-edit" data-group-id="<?=$group_id?>" data-content-id="<?=$key?>" href=""></a></li>
					<?php endif ?>
					<?php if ($deletable): ?>
						<li class="remove"><a class="m-link" rel="modal-checkboxes-confirm-remove" data-confirm="<?=form_prep('<b>'.$content_item_label.'</b>: '.htmlentities($value))?>" data-content-id="<?=$key?>" href=""></a></li>
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
