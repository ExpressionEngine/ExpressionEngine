<?php

if (is_array($choice)):
	if (is_string($key)): ?>
		<div class="field-group-head">
			<?=$key?>
		</div>
	<?php endif ?>
	<?php foreach ($choice as $key => $child):
		$this->embed('ee:_shared/form/fields/select-item', [
			'field_name' => $field_name,
			'key' => $key,
			'attrs' => $attrs,
			'choice' => $child,
			'value' => $value
		]);
	endforeach ?>
<?php else:

$label = isset($choice['label']) ? $choice['label'] : $choice;
$checked = ((is_bool($value) && get_bool_from_string($key) === $value)
	OR ( is_array($value) && in_array($key, $value))
	OR ( ! is_bool($value) && $key == $value)); ?>

<label<?php if ($checked): ?> class="act"<?php endif ?>>
	<input type="<?=($multi) ? 'checkbox' : 'radio'?>" name="<?=$field_name?>" value="<?=htmlentities($key, ENT_QUOTES, 'UTF-8')?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=$attrs?>> <?=lang($label)?>
</label>

<?php endif ?>
