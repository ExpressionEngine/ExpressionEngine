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

		$checked = (in_array(form_prep($value), $values)) ? TRUE : FALSE;

		$class = 'choice block';

		if ($checked)
		{
			$class .= ' chosen';
		}
?>
	<li>
		<label class="<?=$class?>"><?=form_checkbox($field_name.'[]', $key, $checked)?> <?=$value?></label>
<?php
	if (isset($children)):
?>
		<ul>
			<?php $this->view('item', array('options' => $children, 'values' => $values)); ?>
		</ul>
<?php
	endif;
?>
	</li>
<?php
endforeach;
?>
