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
		if ($selected == $key)
		{
			$class .= ' chosen';
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
		<?=form_radio($field_name, $key, ($selected == $key), $extra)?> <?=$value?>
	</label>
	<?php $class = $default_class; ?>
<?php endforeach; ?>
<?php if (count($options) > 5): ?>
</div>
<?php endif; ?>
