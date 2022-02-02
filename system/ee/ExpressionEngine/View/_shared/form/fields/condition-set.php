<?php
    $rule = $rule;
    $match = $match;
    $template = isset($temlates) ? true : false;
    $setTemlatesId = isset($temlates) ? 0 : 1;
    $savedSetId = isset($savedSetId) ? $savedSetId : null;
    $conditionArr = isset($conditionArr) ? $conditionArr : [];

    if ($savedSetId) {
        $id = 'conditionset_block_'.$savedSetId;
        $setId = $savedSetId;
    } else {
        $id = 'new_conditionset_block_'.$setTemlatesId;
        $setId = $setTemlatesId;
    }
?>

<div id="<?=$id?>" class="conditionset-item <?php if ($template) :?>conditionset-temlates-row hidden <?php endif; ?>">
    <a href="#" class="remove-set">
        <i class="fas fa-times alert__close-icon"></i>
    </a>

    <div class="field-conditionset">
        <h4>
            Match
                <span class="match-react-element"><?=$this->embed('_shared/form/fields/dropdown', $match)?></span>
            conditions:
        </h4>

        <div class="rules">
            <?=$this->embed('_shared/form/fields/condition-rule', [
                'setId' => $setId,
                'rule' => $rule,
                'hiddenTemplate' => true
            ]);?>

            <?php 
            if (count($conditionArr)) {
                foreach ($conditionArr as $row) {
                    $rule['value'] = $row['condition_field_id'];
                    $rule['field_name'] = 'condition[set_'.$setId.'][row_'.$row["condition_id"].'][condition_field_id]';
                    $evaluation_rule = $row['evaluation_rule'];
                    $value = $row['value'];
                    $rowId = $row['condition_id'];

                    $this->embed('_shared/form/fields/condition-rule', [
                        'setId' => $setId,
                        'rule' => $rule,
                        'evaluation_rule' => $evaluation_rule,
                        'value' => $value,
                    ]);
                }
            }
            ?>
        </div>

        <a href="#" class="button button--default button--small condition-btn" rel="add_row">Add a Condition</a>
    </div>

    <a href="#" class="add-set">Add another set...</a>
</div>
