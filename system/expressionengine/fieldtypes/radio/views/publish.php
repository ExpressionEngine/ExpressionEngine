<?php
	$class = 'choice mr';
	foreach ($options as $key => $value):
		if ($selected == $key)
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
	<label class="<?=$class?>"><?=form_radio($field_name, $key, ($selected == $key))?> <?=$value?></label>
	<?php $class = 'choice'; ?>
<?php endforeach; ?>