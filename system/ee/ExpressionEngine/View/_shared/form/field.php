<?php
$margin_top = isset($field['margin_top']) ? $field['margin_top'] : false;
$margin_left = isset($field['margin_left']) ? $field['margin_left'] : false;

$class = (isset($field['class'])) ? $field['class'] : '';
$class .= ($margin_top) ? ' add-mrg-top' : '';
$class .= ($margin_left) ? ' add-mrg-left' : '';

// Check for a field name override
if (isset($field['name'])) {
    $field_name = $field['name'];
}
// Get the value of the field
$value = set_value($field_name);
if ($value == '') {
    $value = array_key_exists('value', $field) ? $field['value'] : ee()->config->item($field_name, '', true);
}
// Escape output
if (is_string($value)) {
    $value = form_prep($value, $field_name);
}
$attrs = (isset($field['attrs'])) ? $field['attrs'] : '';
if (isset($field['disabled']) && $field['disabled'] == true) {
    $attrs .= ' disabled="disabled"';
}
if (isset($field['readonly']) && $field['readonly'] == true) {
    $attrs .= ' readonly="readonly"';
}
// This is to handle showing and hiding certain parts
// of the form when a form element changes
if (isset($field['group_toggle'])) {
    $attrs .= " data-group-toggle='" . json_encode($field['group_toggle']) . "'";
    ;
    $attrs .= ' onchange="EE.cp.form_group_toggle(this)"';
}
if (isset($field['maxlength'])) {
    $attrs .= ' maxlength="' . (int) $field['maxlength'] . '"';
}
if (isset($field['placeholder'])) {
    $attrs .= ' placeholder="' . $field['placeholder'] . '"';
}
if (isset($field['group'])) {
    $attrs .= ' data-group="' . $field['group'] . '"';
}
$has_note = isset($field['note']);

$no_results = (in_array($field['type'], array('select')) &&
    isset($field['no_results']) &&
    count($field['choices']) == 0);

// Conditionally show 'mr' class
$mr_class = (! isset($mr) or (isset($mr) && $mr)) ? 'mr' : '';
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
        <div class="<?=$class?>" <?=isset($field['group']) ? ' data-group="' . $field['group'] . '"' : ''?>>
    <?php endif ?>

            <input type="text" name="<?=$field_name?>" value="<?=$value?>"<?=$attrs?> aria-label="<?=$field_name?>">

    <?php if (!empty($class)): ?>
        </div>
    <?php endif ?>
<?php break;
    // no break
case 'short-text': ?>
    <label class="flex-input <?=$class?>">
        <input type="text" name="<?=$field_name?>" value="<?=$value?>"<?=$attrs?>>
        <?php if (isset($field['label'])):?>
            <span class="label-txt"><?=lang($field['label'])?></span>
        <?php endif;?>
    </label>
    <?php break;
case 'number':
    if ($class): ?>
        <div class="<?=$class?>" <?=isset($field['group']) ? ' data-group="' . $field['group'] . '"' : ''?>>
    <?php endif ?>

            <input type="number" name="<?=$field_name?>" value="<?=$value?>"<?=$attrs?> aria-label="<?=$field_name?>">

    <?php if (!empty($class)): ?>
        </div>
    <?php endif ?>
<?php break;
case 'file': ?>
    <input type="file" name="<?=$field_name?>"<?=$attrs?> class="<?=$class?>" aria-label="<?=$field_name?>">
<?php break;
case 'password': ?>
    <input type="password" name="<?=$field_name?>" value="<?=$value?>" autocomplete="<?=($field_name=='verify_password' || $field_name=='password_confirm' ? 'current' : 'new')?>-password"<?=$attrs?> class="<?=$class?>" aria-label="<?=$field_name?>">
<?php break;
case 'hidden': ?>
    <input type="hidden" name="<?=$field_name?>" value="<?=$value?>" aria-label="<?=$field_name?>">
<?php break;

case 'radio_block':
case 'radio':
case 'inline_radio':
case 'checkbox':
if ($field['type'] == 'checkbox' && ! $value) {
    $value = [];
}
?>
    <?php $this->embed('ee:_shared/form/fields/select', [
        'field_name' => $field_name,
        'choices' => $field['choices'],
        'disabled_choices' => isset($field['disabled_choices']) ? $field['disabled_choices'] : null,
        'value' => is_array($value) ? array_unique($value) : $value,
        'scalar' => isset($field['scalar']) ? $field['scalar'] : null,
        'multi' => ($field['type'] == 'checkbox'),
        'nested' => isset($field['nested']) ? $field['nested'] : false,
        'nestableReorder' => isset($nestable_reorder) ? $nestable_reorder : false,
        'selectable' => isset($field['selectable']) ? $field['selectable'] : true,
        'reorderable' => isset($field['reorderable']) ? $field['reorderable'] : false,
        'removable' => isset($field['removable']) ? $field['removable'] : false,
        'editable' => isset($field['editable']) ? $field['editable'] : false,
        'filter_url' => isset($field['filter_url']) ? $field['filter_url'] : null,
        'limit' => isset($field['limit']) ? $field['limit'] : 100,
        'no_results' => isset($field['no_results']) ? $field['no_results'] : null,
        'attrs' => $attrs,
        'group_toggle' => isset($field['group_toggle']) ? $field['group_toggle'] : null,
        'auto_select_parents' => isset($field['auto_select_parents']) ? $field['auto_select_parents'] : false,
        'encode' => isset($field['encode']) ? $field['encode'] : true,
        'force_react' => isset($field['force_react']) ? $field['force_react'] : false,
        'jsonify' => isset($field['jsonify']) ? $field['jsonify'] : false,
        'class' => $class,
        'toggle_all' => isset($field['toggle_all']) ? $field['toggle_all'] : null
    ]); ?>
<?php break;

case 'select':
    if (! $no_results) {
        echo form_dropdown($field_name, $field['choices'], $value, $attrs . ' class="' . $class . '"', isset($field['encode']) ? $field['encode'] : true);
    }

break;
case 'dropdown': ?>
    <?php $this->embed('ee:_shared/form/fields/dropdown', [
        'field_name' => $field_name,
        'choices' => $field['choices'],
        'value' => $value,
        'filter_url' => isset($field['filter_url']) ? $field['filter_url'] : null,
        'limit' => isset($field['limit']) ? $field['limit'] : 100,
        'no_results' => isset($field['no_results']) ? $field['no_results'] : null,
        'group_toggle' => isset($field['group_toggle']) ? $field['group_toggle'] : null,
        'empty_text' => isset($field['empty_text']) ? lang($field['empty_text']) : lang('choose_wisely'),
        'class' => $class,
    ]); ?>
<?php break;

case 'yes_no':
case 'toggle': ?>
    <?php $this->embed('ee:_shared/form/fields/toggle', [
        'yes_no' => ($field['type'] == 'yes_no'),
        'value' => $value,
        'disabled' => (isset($field['disabled']) && $field['disabled'] == true),
        'group_toggle' => isset($field['group_toggle']) ? $field['group_toggle'] : null,
        'class' => $class,
    ]); ?>
<?php break;

case 'textarea':
    if ($class): ?>
        <div class="<?=$class?>" <?=isset($field['group']) ? ' data-group="' . $field['group'] . '"' : ''?>>
    <?php endif ?>
            <textarea aria-label="<?=$field_name?>" name="<?=$field_name?>" <?=(isset($field['cols']) ? "cols=\"{$field['cols']}\"" : "")?> <?=(isset($field['rows']) ? "rows=\"{$field['rows']}\"" : "")?> <?=$attrs?>><?=(isset($field['kill_pipes']) && $field['kill_pipes'] === true) ? str_replace('|', NL, $value) : $value?></textarea>
    <?php if ($margin_top or $margin_left): ?>
        </div>
    <?php endif ?>
<?php break;
    // no break
case 'multiselect': ?>
    <div class="fields-select fields-multiselect <?=$class?>">
        <div class="field-inputs">
            <?php foreach ($field['choices'] as $field_name => $options): ?>
                <label>
                    <span><?=$options['label']?></span>
                    <?=form_dropdown($field_name, $options['choices'], $options['value'])?>
                </label>
            <?php endforeach ?>
        </div>
    </div>
<?php break;
// no break
case 'image': ?>
    <figure class="file-chosen <?=$class?>">
        <div id="<?=$field['id']?>"><img src="<?=$field['image']?>"></div>
        <ul class="toolbar button-group">
            <?php if (! array_key_exists('edit', $field) || $field['edit']): ?>
            <li class="edit"><a class="edit button button--default button--xsmall" href="" title="edit"></a></li>
            <?php endif; ?>
            <li class="remove"><a class="remove button button--default button--xsmall" href="" title="remove"></a></li>
        </ul>
        <input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
    </figure>
<?php break;

case 'slider': 
    $this->embed('slider:single', [
        'min' => isset($field['min']) ? $field['min'] : 0,
        'max' => isset($field['max']) ? $field['max'] : 100,
        'step' => isset($field['step']) ? $field['step'] : 1,
        'name' => $field_name,
        'value' => $value,
        'suffix' => isset($field['suffix']) ? $field['suffix'] : '',
        'prefix' => isset($field['prefix']) ? $field['prefix'] : '',
    ]); 
break;

case 'action_button': ?>
    <a class="button button--secondary tn <?=$class?>" href="<?=$field['link']?>"><?=lang($field['text'])?></a>
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
<?php if (! $grid): ?>
    <?=form_error(str_replace('[]', '', $field_name))?>
    <?php if (isset($errors)) {
        echo $errors->renderError($field_name);
    } ?>
<?php endif;
