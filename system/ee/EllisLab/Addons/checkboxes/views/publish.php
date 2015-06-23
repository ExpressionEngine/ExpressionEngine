<div class="scroll-wrap pr">
<?php

	$depth = 0;
	$queue = array_map(NULL, array_keys($options), $options);

	while ($item = array_shift($queue)):

		if (is_int($item))
		{
			$depth += $item;
			continue;
		}

		list($key, $value) = $item;

		// If the value is an array, then we have children. Add them to the
		// queue with depth markers and set the real value to render the parent.
		if (is_array($value))
		{
			$children = $value['children'];
			$children = array_map(NULL, array_keys($children), $children);

			// $children = [+1, ...$children, -1];
			array_unshift($children, +1);
			array_push($children, -1);

			$queue = array_merge($children, $queue);
			$value = $value['name'];
		}

		$checked = (in_array(form_prep($value), $values)) ? TRUE : FALSE;

		$class = 'choice block';

		if ($checked)
		{
			$class .= ' chosen';
		}

		if ($depth)
		{
			$class .= ' child';
		}

?>
		<label class="<?=$class?>"><?=form_checkbox($field_name.'[]', $key, $checked)?> <?=$value?></label>
<?php
	endwhile;
?>
</div>
