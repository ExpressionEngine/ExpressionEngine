<?php
    $setId = $setId;
    $rule = $rule;
    $hiddenTemplate = isset($hiddenTemplate) ? true : false;
?>
<div class="rule <?php if ($hiddenTemplate) :?>rule-blank-row hidden <?php endif; ?>" >
    <div class="condition-rule-field-wrap" data-new-rule-row-id="new_rule_row_<?=$setId?>">
        <?=$this->embed('_shared/form/fields/dropdown', $rule)?>
    </div>

    <div class="condition-rule-operator-wrap" data-new-rule-row-id="new_rule_row_<?=$setId?>">
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
    </div>

    <div class="condition-rule-value-wrap" data-new-rule-row-id="new_rule_row_<?=$setId?>">
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
