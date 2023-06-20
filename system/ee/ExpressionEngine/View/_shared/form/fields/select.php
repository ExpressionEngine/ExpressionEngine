<?php
$too_many = isset($too_many) ? $too_many : 8;
$class = isset($class) ? $class : '';

if (count($choices) == 0) {
    if (isset($no_results)): ?>
		<div data-input-value="<?=$field_name?>" class="<?=$class?>">
			<?=$this->make('ee:_shared/form/no_results')->render($no_results)?>
		</div>
	<?php endif;

    return;
};

$nested = isset($nested) ? $nested : false;
$encode = isset($encode) ? $encode : true;
$force_react = isset($force_react) ? $force_react : false;
$disabled_choices = isset($disabled_choices) ? $disabled_choices : [];

// Normalize choices into an array to keep order of items, order cannot be
// counted on in a JavaScript object
$normalized_choices = ee('View/Helpers')->normalizedChoices($choices, $nested ?: null);

$has_groupings = false;
foreach ($normalized_choices as $key => $choice) {
    if (isset($choice['section'])) {
        $has_groupings = true;
    }
    if (isset($choice['children'])) {
        $nested = true;
    }
}

$count = ee('View/Helpers')->countChoices($normalized_choices);

// We want to select the first in a radio selection when there is no value set;
// these are the rules that we constitute as having no value
$no_radio_value = ! $multi
    && (
        // FALSE can be a valid value for models with boolString properties
        ($value !== false && $value !== '0' && empty($value) && ! ($value === '' && isset($choices[''])))
        // But if value is FALSE but an empty string is available as a choice, probably not valid
        || ($value === false && ee('View/Helpers')->findLabelForValue('', $normalized_choices) !== false)
    );

// If it's a small list, just render it server-side
if ($count <= $too_many
    && ! ($count > 2 && $multi)
    && ! $nested
    && ! $has_groupings
    && ! $force_react):

    // For radios with no value, set value to first choice
    if ($no_radio_value) {
        $keys = array_keys($choices);
        $value = $keys[0];
    }
    ?>
	<div class="fields-select <?=$class?>" data-input-value="<?=$field_name?>">
		<?php if ($multi) : ?>
		<input type="hidden" name="<?=$field_name?>" />
		<?php endif; ?>
		<?php if (! isset($scalar) && $multi) {
        $field_name .= '[]';
    } ?>
		<div class="field-inputs">
			<?php foreach ($choices as $key => $choice):
                $label = isset($choice['label'])
                    ? lang($choice['label']) : lang($choice);
                $key = isset($choice['value'])
                    ? $choice['value'] : $key;
                $instructions = isset($choice['instructions']) ? $choice['instructions'] : null;
                if ($encode) {
                    $label = ee('Format')->make('Text', $label)->convertToEntities();
                    $instructions = ee('Format')->make('Text', $instructions)->convertToEntities();
                }
                $checked = ((is_bool($value) && get_bool_from_string($key) === $value)
                    or (is_array($value) && in_array($key, $value))
                    or (! is_bool($value) && $key == $value));
                $disabled = in_array($key, $disabled_choices) ? ' disabled' : ''; ?>

				<label class="checkbox-label" <?php if ($checked): ?> class="act"<?php endif ?>>
					<input type="<?=($multi) ? 'checkbox' : 'radio'?>" name="<?=$field_name?>" value="<?=htmlentities($key, ENT_QUOTES, 'UTF-8')?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=isset($attrs) ? $attrs : ''?><?=$disabled?>>
					<div class="checkbox-label__text">
					<?=$label?>
						<?php if ($instructions): ?><i><?=$instructions?></i><?php endif ?>
					</div>
				</label>
			<?php endforeach; ?>
		</div>
	</div>
<?php
// Large/complex list, render it using React
else:
    // If $value is FALSE and we're rendering the field with React, FALSE
    // probably isn't a valid value and probably came from asking the config
    // library for the field's value in form/field.php
    if ($no_radio_value && ($value === false || is_null($value)) && isset($normalized_choices[0]['value'])) {
        $value = $normalized_choices[0]['value'];
    }

    if ($value !== false && $value !== null && ! is_array($value) && ! $multi) {
        $label = ee('View/Helpers')->findLabelForValue($value, $normalized_choices);

        if ($label) {
            $value = [$value => $label];
        }
    } elseif ($multi && ! is_array($value)) {
        $value = [$value];
    }

    // This is a little confusing. Basically, here's what $toggle_all can be and
    // what each state does:
    //   TRUE - Shows "Check All"
    //   FALSE - Shows "Clear All", only used in Relationships
    //   NULL - No toggle-all functionality
    // If it has explicitly been set to FALSE in the field definition, set it
    // to NULL and don't allow it to be shown based on number of items. Otherwise,
    // allow the number of items to decide whether or not to show the option.
    $toggle_all = (isset($toggle_all) && $toggle_all === false) ? null : true;
    $toggle_all = ($toggle_all !== null && $count > 2 && $multi) ? true : null;

    $component = [
        'name' => $field_name,
        'items' => $normalized_choices,
        'selected' => $value,
        'multi' => $multi,
        'nested' => $nested,
        'nestableReorder' => isset($nestable_reorder) ? $nestable_reorder : false,
        'disabled' => isset($disabled) ? $disabled : false,
        'disabledChoices' => isset($disabled_choices) ? $disabled_choices : false,
        'unremovableChoices' => isset($unremovable_choices) ? $unremovable_choices : false,
        'autoSelectParents' => isset($auto_select_parents) ? $auto_select_parents : null,
        'tooMany' => $too_many,
        'tooManyLimit' => isset($tooManyLimit) ? $tooManyLimit : null,
        'filterUrl' => isset($filter_url) ? $filter_url : null,
        'limit' => isset($limit) ? $limit : 100,
        'toggleAll' => $toggle_all,
        'groupToggle' => isset($group_toggle) ? $group_toggle : null,
        'editing' => isset($editing) ? $editing : null,
        'manageable' => isset($manageable) ? $manageable : null,
        'addLabel' => isset($add_btn_label) ? $add_btn_label : null,
        'selectable' => isset($selectable) ? $selectable : true,
        'reorderable' => isset($reorderable) ? $reorderable : false,
        'removable' => isset($removable) ? $removable : false,
        'editable' => isset($editable) ? $editable : false,
        'jsonify' => isset($jsonify) ? $jsonify : false,
        'manageLabel' => isset($manage_label) ? $manage_label : null,
        'reorderAjaxUrl' => isset($reorder_ajax_url) ? $reorder_ajax_url : null,
        'noResults' => isset($no_results['text']) ? lang($no_results['text']) : null,
        'splitForTwo' => isset($split_for_two) ? $split_for_two : null,
        'toggles' => isset($toggles) ? $toggles : []
    ];
    ?>
	<div data-select-react="<?=base64_encode(json_encode($component))?>" data-input-value="<?=$field_name?>" class="<?=$class?>">
		<div class="fields-select">
			<div class="field-inputs">
				<label class="field-loading">
					<?=lang('loading')?><span></span>
				</label>
			</div>
		</div>
	</div>
<?php endif ?>
