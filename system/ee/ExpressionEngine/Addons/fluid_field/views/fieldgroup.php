<div class="fluid__item <?php if ($reorderable) : ?>fluid__item--reorderable<?php endif ?>" data-field-name="<?= $field_group->short_name ?>" data-field-type="field_group">
    <div class="fluid__item-content">
        <div class="fluid__item-fieldset ">

            <?php $this->embed('fluid_field:item-tools'); ?>

            <div class="field-instruct">
                <label>
                    <i class="icon--reorder reorder"></i>
                    <?= $field_group->group_name ?> <?php if ($show_field_type) : ?><span class="faded">(group)</span><?php endif; ?>
                </label>
                <?=(isset($field_name_prefix) ? $field_group->getNameBadge($field_name_prefix) : '')?>
                <em><?= $field_group->group_description ?></em>
            </div>
        </div>

        <?php foreach ($field_group_fields as $field) : ?>
            <?php if ($field->getType() !== 'fluid_field'): ?>
                <div class="fluid__item-field no-drag <?=($field->getType() == 'grid' || $field->getType() == 'file_grid') ? 'fieldset-faux' : ''?>" data-field-type="<?= $field->getType() ?>">

                    <div class="field-instruct">
                        <label>
                            <?=$field->getItem('field_label')?> <?php if ($show_field_type): ?><span class="faded">(<?=$field->getType()?>)</span><?php endif ?>
                        </label>
                        <?=(isset($field_name_prefix) ? $field->getNameBadge($field_name_prefix) : '')?>
                        <em><?=$field->getItem('field_instructions')?></em>
                    </div>
                    <?php
                        $field_name = $field->getName();
                        $form = $field->getForm();

                        if (strpos($form, 'name="' . $field_name . '"') === false) {
                            echo form_hidden($field_name, 1);
                        }

                        echo $form;
                    ?>
                    <?php isset($errors) ? $errors->renderError($field_name) : '' ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

</div>