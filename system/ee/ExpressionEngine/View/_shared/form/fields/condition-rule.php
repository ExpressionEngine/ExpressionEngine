<?php
    $setId = $setId;
    $rule = $rule;
    $hiddenTemplate = isset($hiddenTemplate) ? true : false;
    $evaluation_rule = isset($evaluation_rule) ? $evaluation_rule : null;
    $value = isset($value) ? $value : '';
    $rowId = isset($$rowId) ? $rowId : null;

    if ($hiddenTemplate) {
        $ruleRowId = 'new_rule_row_'.$setId;
    } else {
        $ruleRowId = 'rule_row_'.$setId;

        $operatorLabelArr = [];

        foreach ($fieldsList as $item) {
            if ($item['field_id'] == $rule['value']) {
                $newArr = $item['evaluationRules'];
            }
        }

        var_dump($newArr);

        $operator = [
            'choices' => ee('View/Helpers')->normalizedChoices($item['evaluationRules']),
            'value' => $evaluation_rule,
            'too_many' => 20,
            'class' => 'condition-rule-operator',
            'empty_text' => 'Select a Field',
            'field_name' => 'condition[set_'.$setId.'][row_'.$rowId.'][evaluation_rule]',
            'conditional_toggle' => 'operator',
            'is_required' => false
        ];
    }
?>
<div class="rule <?php if ($hiddenTemplate) :?>rule-blank-row hidden <?php endif; ?>" >
    <div class="condition-rule-field-wrap" data-new-rule-row-id="<?=$ruleRowId?>">
        <?=$this->embed('_shared/form/fields/dropdown', $rule)?>
    </div>

    <div class="condition-rule-operator-wrap" data-new-rule-row-id="<?=$ruleRowId?>">
        <?php if ($hiddenTemplate) : ?>
            <select name="" id="" class="empty-select"></select>

            <div data-input-value="condition-rule-operator" class="condition-rule-operator" style="display: none;">
                <div class="fields-select-drop">
                    <div class="select">
                        <div class="select__button">
                            <label class="select__button-label"></label>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>

        <?php endif; ?>
    </div>

    <div class="condition-rule-value-wrap" data-new-rule-row-id="<?=$ruleRowId?>">
        <input type="text">
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