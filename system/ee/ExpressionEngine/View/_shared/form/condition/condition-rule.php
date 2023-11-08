<?php
    $setId = $setId;
    $conditionFieldVal = $conditionFieldVal;
    $hiddenTemplate = isset($hiddenTemplate) ? true : false;
    $evaluation_rule = isset($evaluation_rule) ? $evaluation_rule : null;
    $value = isset($value) ? $value : null;
    $rowId = isset($rowId) ? $rowId : '';
    $error = isset($error) ? $error : false;

    if ($hiddenTemplate) {
        $ruleRowId = 'new_rule_row_' . $setId;

        if ($setId) {
            $conditionFieldVal['field_name'] = 'condition[' . $setId . '][new_row_0][condition_field_id]';
        } else {
            $conditionFieldVal['field_name'] = 'condition[new_set_0][new_row_0][condition_field_id]';
        }
    } else {
        if ((strpos($rowId, 'new_') !== false)) {
            $str = str_replace('new_row_', '', $rowId);
            $ruleRowId = 'new_rule_row_' . $str;
        } else {
            $ruleRowId = 'rule_row_' . $setId;
        }

        $operatorRuleArr = [];
        $operatorFieldDefault = [];
        $valueOptions = null;
        $valueFieldDefault = [];
        $valueType = ['type' => 'text'];

        if ($evaluation_rule) {
            foreach ($fieldsList[$conditionFieldVal['value']]['evaluationRules'] as $ruleName => $ruleInfo) {
                $operatorRuleArr[$ruleName] = $ruleInfo['text'];

                if ($evaluation_rule == $ruleName) {
                    $valueType = $ruleInfo;
                }
            }

            $operatorFieldDefault = [
                'choices' => ee('View/Helpers')->normalizedChoices($operatorRuleArr),
                'value' => $evaluation_rule,
                'too_many' => 20,
                'class' => 'condition-rule-operator',
                'empty_text' => 'Select a Field',
                'field_name' => 'condition['. $setId .']['. $rowId .'][evaluation_rule]',
                'conditional_toggle' => 'operator',
                'is_required' => false
            ];
        }

        if ($valueType['type'] == 'select') {
            $valueOptions = $fieldsList[$conditionFieldVal['value']]['evaluationValues'];

            $valueFieldDefault = [
                'choices' => ee('View/Helpers')->normalizedChoices($valueOptions),
                'value' => $value,
                'too_many' => 20,
                'class' => 'condition-rule-value',
                'empty_text' => 'Select a Field',
                'field_name' => 'condition['. $setId .']['. $rowId .'][value]',
                'is_required' => false
            ];
        }
    }
?>
<div class="rule <?php if ($hiddenTemplate) :?>rule-blank-row hidden <?php endif; ?>" >
    <div class="condition-rule-field-wrap <?php if ($error) { echo 'required invalid'; } ?>" data-new-rule-row-id="<?=$ruleRowId?>">
        <?=$this->embed('_shared/form/fields/dropdown', $conditionFieldVal)?>
        <?php if ($error) : ?><em class="ee-form-error-message">This field is required.</em><?php endif; ?>
    </div>

    <div class="condition-rule-operator-wrap" data-new-rule-row-id="<?=$ruleRowId?>">
        <?php if ($hiddenTemplate || empty($operatorFieldDefault)) : ?>
            <select name="" id="" class="empty-select" disabled="disabled" aria-label="<?=lang('condition_rule_operator')?>"></select>

            <div data-input-value="condition-rule-operator" class="condition-rule-operator" style="display: none;">
                <div class="fields-select-drop">
                    <div class="select">
                        <div class="select__button">
                            <label class="select__button-label"></label>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: 
            echo $this->embed('_shared/form/fields/dropdown', $operatorFieldDefault);
        endif; ?>
    </div>

    <div class="condition-rule-value-wrap" data-new-rule-row-id="<?=$ruleRowId?>">
        <?php
            if ($hiddenTemplate || $error) :
                echo '<input type="text" disabled="disabled" aria-label="' . lang('conditional_rule_value') . '">';
            elseif (!$hiddenTemplate && ($valueType['type'] == 'select')) :
                $this->embed('_shared/form/fields/dropdown', $valueFieldDefault);
            elseif (!$hiddenTemplate && !($valueType['type'] == null)) :
        ?>
            <input type="text" value="<?=$value?>" name="condition[<?=$setId?>][<?=$rowId?>][value]" aria-label="<?=lang('conditional_rule_value')?>">>
        <?php endif; ?>
    </div>

    <div class="delete_rule">
        <button type="button" rel="remove_row" class="button button--small button--default">
        
            <span class="danger-link" title="<?=lang('remove_row')?>">
                <i class="fal fa-trash-alt">
                    <span class="hidden"><?=lang('remove_row')?></span>
                </i>
            </span>
        </button>
    </div>
</div>
