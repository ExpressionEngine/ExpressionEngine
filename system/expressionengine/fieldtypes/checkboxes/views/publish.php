<?php if (count($options) > 5): ?>
<div class="scroll-wrap">
<?php endif; ?>
<?php
	$default_class = 'choice';

	if (count($options) > 2)
	{
		$default_class .= ' block';
		$class = $default_class;
	}
	else
	{
		$class = $default_class . ' mr';
	}

	foreach ($options as $key => $value):
		$checked = (in_array(form_prep($key), $values)) ? TRUE : FALSE;

		if ($checked)
		{
			$class = ' chosen';
		}

		if ($key == 'y' && $value == lang('yes'))
		{
			$class .= ' yes';
		}
		elseif ($key == 'n' && $value == lang('no'))
		{
			$class .= ' no';
		}
?>
	<label class="<?=$class?>">
		<?=form_checkbox($field_name, $key, $checked)?> <?=$value?>
	</label>
	<?php $class = $default_class; ?>
<?php endforeach; ?>
<?php if (count($options) > 2): ?>
</div>
<?php endif; ?>