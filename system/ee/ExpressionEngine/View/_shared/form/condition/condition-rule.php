<?php
    $setId = $setId;
    $conditionFieldVal = $conditionFieldVal;
    $hiddenTemplate = isset($hiddenTemplate) ? true : false;
    $evaluation_rule = isset($evaluation_rule) ? $evaluation_rule : null;
    $value = isset($value) ? $value : null;
    $rowId = isset($rowId) ? $rowId : null;
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
        $operaionFieldDefault = [];

        if ($evaluation_rule) {
            foreach ($fieldsList[$conditionFieldVal['value']]['evaluationRules'] as $ruleName => $ruleInfo) {
                $operatorRuleArr[$ruleName] = $ruleInfo['text'];
            }

            $operaionFieldDefault = [
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
    }
?>
<div class="rule <?php if ($hiddenTemplate) :?>rule-blank-row hidden <?php endif; ?>" >
    <div class="condition-rule-field-wrap <?php if ($error) { echo 'required invalid'; } ?>" data-new-rule-row-id="<?=$ruleRowId?>">
        <?=$this->embed('_shared/form/fields/dropdown', $conditionFieldVal)?>
        <?php if ($error) : ?><em class="ee-form-error-message">This field is required.</em><?php endif; ?>
    </div>

    <div class="condition-rule-operator-wrap" data-new-rule-row-id="<?=$ruleRowId?>">
        <?php if ($hiddenTemplate || empty($operaionFieldDefault)) : ?>
            <select name="" id="" class="empty-select" disabled="disabled"></select>

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
            echo $this->embed('_shared/form/fields/dropdown', $operaionFieldDefault);
        endif; ?>
    </div>

    <div class="condition-rule-value-wrap" data-new-rule-row-id="<?=$ruleRowId?>">
        <input type="text"
            <?php if (!$hiddenTemplate) : ?>value="<?=$value?>" name="condition[<?=$setId?>][<?=$rowId?>][value]"<?php endif; ?>
            <?php if ((!$hiddenTemplate && trim($value) === '') && !is_null($value)) : ?>style="display: none;"<?php endif; ?>
            <?php if (is_null($value)) : ?>disabled='disabled'<?php endif; ?>
        >
    </div>

    <div class="delete_rule">
        <button type="button" rel="remove_row" class="button button--small button--default">
        
            <span class="danger-link" title="<?=lang('remove_row')?>">
                <i class="fas fa-trash-alt">
                    <span class="hidden"><?=lang('remove_row')?></span>
                </i>
            </span>
        </button>
    </div>
</div>