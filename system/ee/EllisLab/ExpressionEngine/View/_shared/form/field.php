<?php
// Check for a field name override
if (isset($field['name']))
{
	$field_name = $field['name'];
}
// Get the value of the field
$value = set_value($field_name);
if ($value == '')
{
	$value = isset($field['value']) ? $field['value'] : ee()->config->item($field_name, '', TRUE);
}
// Escape output
if (is_string($value))
{
	$value = form_prep($value, $field_name);
}
$attrs = (isset($field['attrs'])) ? $field['attrs'] : '';
if (isset($field['disabled']) && $field['disabled'] == TRUE)
{
	$attrs = ' disabled="disabled"';
}
// This is to handle showing and hiding certain parts
// of the form when a form element changes
if (isset($field['group_toggle']))
{
	$attrs .= " data-group-toggle='".json_encode($field['group_toggle'])."'";;
	$attrs .= ' onchange="EE.cp.form_group_toggle(this)"';
}
if (isset($field['maxlength']))
{
	$attrs .= ' maxlength="'.(int) $field['maxlength'].'"';
}
if (isset($field['placeholder']))
{
	$attrs .= ' placeholder="'.$field['placeholder'].'"';
}
$has_note = isset($field['note']);

$no_results = (in_array($field['type'], array('checkbox', 'radio', 'select')) &&
	isset($field['no_results']) &&
	count($field['choices']) == 0);
?>
<?php if ($no_results): ?>
	<?php $this->embed('ee:_shared/form/no_results', $field['no_results']); ?>
<?php endif ?>
<?php if ($has_note): ?>
	<div class="setting-note">
<?php endif ?>
<?php switch ($field['type']):
case 'text': ?>
	<input type="text" name="<?=$field_name?>" value="<?=$value?>"<?=$attrs?>>
<?php break;
case 'short-text': ?>
	<label class="short-txt"><input type="text" name="<?=$field_name?>" value="<?=$value?>"<?=$attrs?>> <?=lang($field['label'])?></label>
<?php break;
case 'file': ?>
	<input type="file" name="<?=$field_name?>"<?=$attrs?>>
<?php break;
case 'password': ?>
	<input type="password" name="<?=$field_name?>" value="<?=$value?>" autocomplete="off"<?=$attrs?>>
<?php break;
case 'hidden': ?>
	<input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
<?php break;

case 'radio_block': ?>
	<?php foreach ($field['choices'] as $key => $choice):
		$label = $choice['label'];
		$checked = ($key == $value); ?>
		<label class="choice mr block <?=($checked) ? 'chosen' : ''?>"><input type="radio" name="<?=$field_name?>" value="<?=$key?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=$attrs?>> <?=lang($label)?></label>
		<?php if ( ! empty($choice['html'])): ?><?=$choice['html']?><?php endif ?>
	<?php endforeach ?>
<?php break;

case 'radio': ?>
	<?php foreach ($field['choices'] as $key => $label):
		$checked = ($key == $value); ?>
		<label class="choice mr block <?=($checked) ? 'chosen' : ''?>"><input type="radio" name="<?=$field_name?>" value="<?=$key?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=$attrs?>> <?=lang($label)?></label>
	<?php endforeach ?>
<?php break;

case 'inline_radio': ?>
	<?php foreach ($field['choices'] as $key => $label):
		$checked = ((is_bool($value) && get_bool_from_string($key) === $value) OR ( ! is_bool($value) && $key == $value)); ?>
		<label class="choice mr <?=($checked) ? 'chosen' : ''?>"><input type="radio" name="<?=$field_name?>" value="<?=$key?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=$attrs?>> <?=lang($label)?></label>
	<?php endforeach ?>
<?php break;

case 'yes_no': ?>
	<label class="choice mr<?php if (get_bool_from_string($value)):?> chosen<?php endif ?> yes"><input type="radio" name="<?=$field_name?>" value="y"<?php if (get_bool_from_string($value)):?> checked="checked"<?php endif ?><?=$attrs?>> <?=lang('yes')?></label>
	<label class="choice <?php if (get_bool_from_string($value) === FALSE):?> chosen<?php endif ?> no"><input type="radio" name="<?=$field_name?>" value="n"<?php if (get_bool_from_string($value) === FALSE):?> checked="checked"<?php endif ?><?=$attrs?>> <?=lang('no')?></label>
<?php break;

case 'select': ?>
<?php if ( ! $no_results) echo form_dropdown($field_name, $field['choices'], $value, $attrs, isset($field['encode']) ? $field['encode'] : TRUE); ?>
<?php break;

case 'checkbox': ?>
	<?php if ( ! isset($field['scalar'])) $field_name .= '[]'; ?>
	<?php if (isset($field['nested']) && $field['nested']): ?>
		<div class="scroll-wrap pr">
			<ul class="nested-list">
				<?php $this->embed('ee:_shared/form/nested_checkbox', array(
					'field_name' => $field_name,
					'attrs' => $attrs,
					'choices' => $field['choices'],
					'disabled_choices' => (isset($field['disabled_choices'])) ? $field['disabled_choices'] : array(),
					'value' => $value,
				)); ?>
			</ul>
		</div>
	<?php else: ?>
	<?php if (isset($field['wrap']) && $field['wrap']): ?>
		<div class="scroll-wrap">
	<?php endif ?>
		<?php foreach ($field['choices'] as $key => $label):
			if (is_array($value))
			{
				$selected = in_array($key, $value);
			}
			else
			{
				$selected = ((string) $value == (string) $key);
			}

			$disabled = FALSE;
			if (isset($field['disabled_choices']))
			{
				$disabled = in_array($key, $field['disabled_choices']);
			}
		?>
			<label class="choice block<?php if ($selected):?> chosen<?php endif ?><?php if ($disabled):?> disable<?php endif ?>">
				<input type="checkbox" name="<?=$field_name?>" value="<?=$key?>"<?php if ($selected):?> checked="checked"<?php endif ?><?php if ($disabled):?> disabled="disabled"<?php endif ?><?=$attrs?>> <?=$label?>
			</label>
		<?php endforeach ?>
	<?php if (isset($field['wrap']) && $field['wrap']): ?>
		</div>
	<?php endif ?>
	<?php endif ?>
<?php break;

case 'textarea': ?>
	<textarea name="<?=$field_name?>" cols="" rows=""<?=$attrs?>>
<?=(isset($field['kill_pipes']) && $field['kill_pipes'] === TRUE) ? str_replace('|', NL, $value) : $value?>
</textarea>
<?php break;

case 'multiselect': ?>
	<div class="scroll-wrap">
		<?php foreach ($field['choices'] as $field_name => $options): ?>
			<label class="choice block chosen"><?=$options['label']?>
				<?=form_dropdown($field_name, $options['choices'], $options['value'])?>
			</label>
		<?php endforeach ?>
	</div>
<?php break;

case 'image': ?>
	<figure class="file-chosen">
		<div id="<?=$field['id']?>"><img src="<?=$field['image']?>"></div>
		<ul class="toolbar">
			<?php if( ! array_key_exists('edit', $field) || $field['edit']): ?>
			<li class="edit"><a href="" title="edit"></a></li>
			<?php endif; ?>
			<li class="remove"><a href="" title="remove"></a></li>
		</ul>
		<input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
	</figure>
<?php break;

case 'slider': ?>
	<div class="slider">
		<input type="range" rel="range-value"
			id="<?=$field_name?>"
			name="<?=$field_name?>"
			value="<?=$value?>"
			min="<?= isset($field['min']) ? $field['min'] : 0 ?>"
			max="<?= isset($field['max']) ? $field['max'] : 100 ?>"
			step="<?= isset($field['step']) ? $field['step'] : 1 ?>"
			<?= isset($field['list']) ? "list='{$field['list']}'" : NULL ?>
		>
		<div class="slider-output">
			<output class="range-value" for="<?=$field_name?>"><?=$value?></output><?= isset($field['unit']) ? $field['unit'] : '%' ?>
		</div>
	</div>
<?php break;

case 'action_button': ?>
	<a class="btn tn action <?=$field['class']?>" href="<?=$field['link']?>"><?=lang($field['text'])?></a>
<?php break;

case 'html': ?>
	<?=$field['content']?>
<?php endswitch ?>
<?php if ($has_note): ?>
	<em><?=$field['note']?></em>
</div>
<?php endif ?>
<?php if ( ! $grid): ?>
	<?=form_error(rtrim($field_name, '[]'))?>
	<?php if (isset($errors)) echo $errors->renderError($field_name); ?>
<?php endif ?>
