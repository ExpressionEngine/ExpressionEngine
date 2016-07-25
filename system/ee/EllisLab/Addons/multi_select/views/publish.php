<?php if (count($options) > 5): ?>
<div class="scroll-wrap">
<?php endif; ?>
<?php
	$class = 'choice block';

	foreach ($options as $key => $value):
		$checked = (in_array($key, $values) OR in_array(form_prep($key), $values));

		if ($checked)
		{
			$class .= ' chosen';
		}
?>
	<label class="<?=$class?>">
		<?=form_checkbox($field_name . '[]', $key, $checked, $extra)?> <?=form_prep($value)?>
	</label>
	<?php $class = 'choice block'; ?>
<?php endforeach; ?>
<?php if (count($options) > 5): ?>
</div>
<?php endif; ?>
