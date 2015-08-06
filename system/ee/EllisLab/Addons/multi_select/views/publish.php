<?php if (count($options) > 5): ?>
<div class="scroll-wrap">
<?php endif; ?>
<?php
	$class = 'choice block';

	foreach ($options as $key => $value):
		$checked = (in_array(form_prep($key), $values)) ? TRUE : FALSE;

		if ($checked)
		{
			$class .= ' chosen';
		}
?>
	<label class="<?=$class?>">
		<?=form_checkbox($field_name . '[]', $key, $checked)?> <?=$value?>
	</label>
	<?php $class = 'choice block'; ?>
<?php endforeach; ?>
<?php if (count($options) > 5): ?>
</div>
<?php endif; ?>
