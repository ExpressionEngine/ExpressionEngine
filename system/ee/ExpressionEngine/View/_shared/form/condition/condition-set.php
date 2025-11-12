<?php
    $matchVal = $matchVal;
    $conditionFieldVal = $conditionFieldVal;
    $template = isset($temlates) ? true : false;
    $setTemlatesId = isset($temlates) ? 0 : 1;
    $savedSetId = isset($conditionSetId) ? $conditionSetId : '';
    $conditions = isset($conditions) ? $conditions : [];
    $conditionRowId = isset($conditionRowId) ? $conditionRowId : '';

    if ((strpos($savedSetId, 'new_') !== false)) {
        $setBlockId = 'new_conditionset_block_' . $setTemlatesId;
        $setId = $savedSetId;
    } elseif ($savedSetId) {
        $setBlockId = 'conditionset_block_' . $savedSetId;
        $setId = $savedSetId;
    } else {
        $setBlockId = 'new_conditionset_block_' . $setTemlatesId;
        $setId = $setTemlatesId;
    }
?>

<div id="<?=$setBlockId?>" class="conditionset-item <?php if ($template) :?>conditionset-temlates-row hidden <?php endif; ?>">
    <a href="#" class="remove-set">
        <span class="sr-only"><?=lang('remove_set')?></span>
        <i class="fal fa-times alert__close-icon"></i>
    </a>

    <div class="field-conditionset">
        <h4>
            Match
                <span class="match-react-element"><?=$this->embed('_shared/form/fields/dropdown', $matchVal)?></span>
            conditions:
        </h4>

        <div class="rules">
            <?=$this->embed('_shared/form/condition/condition-rule', [
                'setId' => $setId,
                'conditionFieldVal' => $conditionFieldVal,
                'hiddenTemplate' => true
            ]);?>
            <?php
            if (count($conditions)) {
                foreach ($conditions['conditions'] as $conditionRow) {
                    $conditionFieldVal['value'] = $conditionRow['condition_field_id'];
                    $rowId = $conditionRow['condition_id'];
                    $conditionFieldVal['field_name'] = 'condition[' . $setId . '][' . $rowId . '][condition_field_id]';
                    $evaluation_rule = $conditionRow['evaluation_rule'];
                    $value = $conditionRow['value'];
                    $error = isset($conditionRow['errors']) ? $conditionRow['errors'] : false;

                    $this->embed('_shared/form/condition/condition-rule', [
                        'setId' => $setId,
                        'rowId' => $rowId,
                        'conditionFieldVal' => $conditionFieldVal,
                        'evaluation_rule' => $evaluation_rule,
                        'value' => $value,
                        'error' => $error
                    ]);
                }
            }
            ?>
        </div>

        <a href="#" class="button button--default button--small condition-btn" rel="add_row">Add a Condition</a>
    </div>

    <a href="#" class="add-set">Add another set...</a>
</div>
