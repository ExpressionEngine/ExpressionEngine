<div class="fluid__item <?php if ($reorderable): ?>fluid__item--reorderable<?php endif ?>" data-field-name="<?=$field_name?>" data-field-type="<?=$field->getType()?>">
    <div class="fluid__item-content">
        <div class="fluid__item-fieldset ">

            <?php $this->embed('fluid_field:item-tools'); ?>

            <div class="field-instruct">
                <label>
                    <i class="icon--reorder reorder"></i>
                    <?=$field->getItem('field_label')?> <?php if ($show_field_type): ?><span class="faded">(<?=$field->getType()?>)</span><?php endif ?>
                </label>
                <?=(isset($field_name_prefix) ? $field->getNameBadge($field_name_prefix) : '')?>
                <em><?=$field->getItem('field_instructions')?></em>
            </div>
        </div>

        <div class="fluid__item-field no-drag <?=($field->getType() == 'grid' || $field->getType() == 'file_grid') ? 'fieldset-faux' : ''?>">
        <?php
            $field_name = $field->getName();
            $form = $field->getForm();

            if (strpos($form, 'name="' . $field_name . '"') === false) {
                echo form_hidden($field_name, 1);
            }

            echo $form;
        ?>
        <?=isset($errors) ? $errors->renderError($field_name) : ''?>
        </div>
    </div>

</div>
