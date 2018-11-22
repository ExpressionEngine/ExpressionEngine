<?php
$margin_top = isset($field['margin_top']) ? $field['margin_top'] : FALSE;
$margin_left = isset($field['margin_left']) ? $field['margin_left'] : FALSE;

$class = (isset($field['class'])) ? $field['class'] : '';
$class .= ($margin_top) ? ' add-mrg-top' : '';
$class .= ($margin_left) ? ' add-mrg-left' : '';

// Check for a field name override
if (isset($field['name']))
{
	$field_name = $field['name'];
}
// Get the value of the field
$value = set_value($field_name);
if ($value == '')
{
	$value = array_key_exists('value', $field) ? $field['value'] : ee()->config->item($field_name, '', TRUE);
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
if (isset($field['group']))
{
	$attrs .= ' data-group="'.$field['group'].'"';
}
$has_note = isset($field['note']);

$no_results = (in_array($field['type'], array('select')) &&
	isset($field['no_results']) &&
	count($field['choices']) == 0);

// Conditionally show 'mr' class
$mr_class = ( ! isset($mr) OR (isset($mr) && $mr)) ? 'mr' : '';
?>
<?php if ($no_results): ?>
	<?php $this->embed('ee:_shared/form/no_results', $field['no_results']); ?>
<?php endif ?>
<?php if ($has_note): ?>
	<div class="setting-note">
<?php endif ?>
<?php switch ($field['type']):
case 'text':
	if ($class): ?>
		<div class="<?=$class?>" <?=isset($field['group']) ? ' data-group="'.$field['group'].'"' : ''?>>
	<?php endif ?>

			<input type="text" name="<?=$field_name?>" value="<?=$value?>"<?=$attrs?>>

	<?php if ($margin_top OR $margin_left): ?>
		</div>
	<?php endif ?>
<?php break;
case 'short-text': ?>
	<label class="flex-input <?=$class?>">
		<input type="text" name="<?=$field_name?>" value="<?=$value?>"<?=$attrs?>>
		<span class="label-txt"><?=lang($field['label'])?></span>
	</label>
<?php break;
case 'file': ?>
	<input type="file" name="<?=$field_name?>"<?=$attrs?> class="<?=$class?>">
<?php break;
case 'password': ?>
	<input type="password" name="<?=$field_name?>" value="<?=$value?>" autocomplete="new-password"<?=$attrs?> class="<?=$class?>">
<?php break;
case 'hidden': ?>
	<input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
<?php break;

case 'radio_block':
case 'radio':
case 'inline_radio':
case 'checkbox':
if ($field['type'] == 'checkbox' && ! $value) $value = [];
?>
	<?php $this->embed('ee:_shared/form/fields/select', [
		'field_name' => $field_name,
		'choices' => $field['choices'],
		'disabled_choices' => isset($field['disabled_choices']) ? $field['disabled_choices'] : NULL,
		'value' => $value,
		'scalar' => isset($field['scalar']) ? $field['scalar'] : NULL,
		'multi' => ($field['type'] == 'checkbox'),
		'nested' => isset($field['nested']) ? $field['nested'] : FALSE,
		'selectable' => isset($field['selectable']) ? $field['selectable'] : TRUE,
		'reorderable' => isset($field['reorderable']) ? $field['reorderable'] : FALSE,
		'removable' => isset($field['removable']) ? $field['removable'] : FALSE,
		'editable' => isset($field['editable']) ? $field['editable'] : FALSE,
		'filter_url' => isset($field['filter_url']) ? $field['filter_url'] : NULL,
		'limit' => isset($field['limit']) ? $field['limit'] : 100,
		'no_results' => isset($field['no_results']) ? $field['no_results'] : NULL,
		'attrs' => $attrs,
		'group_toggle' => isset($field['group_toggle']) ? $field['group_toggle'] : NULL,
		'auto_select_parents' => isset($field['auto_select_parents']) ? $field['auto_select_parents'] : FALSE,
		'encode' => isset($field['encode']) ? $field['encode'] : TRUE,
		'force_react' => isset($field['force_react']) ? $field['force_react'] : FALSE,
		'class' => $class,
		'toggle_all' => isset($field['toggle_all']) ? $field['toggle_all'] : NULL
	]); ?>
<?php break;

case 'select':
	if ( ! $no_results) echo form_dropdown($field_name, $field['choices'], $value, $attrs.' class="'.$class.'"', isset($field['encode']) ? $field['encode'] : TRUE);
break;
case 'dropdown': ?>
	<?php $this->embed('ee:_shared/form/fields/dropdown', [
		'field_name' => $field_name,
		'choices' => $field['choices'],
		'value' => $value,
		'filter_url' => isset($field['filter_url']) ? $field['filter_url'] : NULL,
		'limit' => isset($field['limit']) ? $field['limit'] : 100,
		'no_results' => isset($field['no_results']) ? $field['no_results'] : NULL,
		'group_toggle' => isset($field['group_toggle']) ? $field['group_toggle'] : NULL,
		'empty_text' => isset($field['empty_text']) ? lang($field['empty_text']) : lang('choose_wisely'),
		'class' => $class,
	]); ?>
<?php break;

case 'yes_no':
case 'toggle': ?>
	<?php $this->embed('ee:_shared/form/fields/toggle', [
		'yes_no' => ($field['type'] == 'yes_no'),
		'value' => $value,
		'disabled' => (isset($field['disabled']) && $field['disabled'] == TRUE),
		'group_toggle' => isset($field['group_toggle']) ? $field['group_toggle'] : NULL,
		'class' => $class,
	]); ?>
<?php break;

case 'textarea':
	if ($class): ?>
		<div class="<?=$class?>" <?=isset($field['group']) ? ' data-group="'.$field['group'].'"' : ''?>>
	<?php endif ?>

			<textarea name="<?=$field_name?>" cols="" rows=""<?=$attrs?>><?=(isset($field['kill_pipes']) && $field['kill_pipes'] === TRUE) ? str_replace('|', NL, $value) : $value?></textarea>

	<?php if ($margin_top OR $margin_left): ?>
		</div>
	<?php endif ?>
<?php break;

case 'multiselect': ?>
	<div class="fields-select" class="<?=$class?>">
		<div class="field-inputs">
			<?php foreach ($field['choices'] as $field_name => $options): ?>
				<label><?=$options['label']?>
					<?=form_dropdown($field_name, $options['choices'], $options['value'])?>
				</label>
			<?php endforeach ?>
		</div>
	</div>
<?php break;

case 'image': ?>
	<figure class="file-chosen <?=$class?>">
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
	<div class="slider <?=$class?>">
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
	<a class="btn tn action <?=$class?>" href="<?=$field['link']?>"><?=lang($field['text'])?></a>
<?php break;

case 'html':
	if ($class): ?>
		<div class="<?=$class?>">
	<?php endif ?>
		<?=$field['content']?>
	<?php if ($class): ?>
		</div>
	<?php endif ?>
<?php endswitch ?>
<?php if ($has_note): ?>
	<em><?=$field['note']?></em>
</div>
<?php endif ?>
<?php if ( ! $grid): ?>
	<?=form_error(rtrim($field_name, '[]'))?>
	<?php if (isset($errors)) echo $errors->renderError($field_name); ?>
<?php endif;
