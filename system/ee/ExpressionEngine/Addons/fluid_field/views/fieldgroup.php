<div class="fluid__item <?php if ($reorderable) : ?>fluid__item--reorderable<?php endif ?>" data-field-name="<?= $field_group->group_name ?>" data-field-type="field_group">
    <div class="fluid__item-content">
        <div class="fluid__item-fieldset ">

            <div class="fluid__item-tools fluid__item-tools--item-open">

                <?php if (empty($is_bulk_edit)) : ?>
                    <button type="button" data-dropdown-offset="0px, -30px" data-dropdown-pos="bottom-end" class="fluid__item-tool js-dropdown-toggle"><i class="fas fa-fw fa-cog"></i></button>
                    <div class="dropdown">
                        <a href class="dropdown__link js-toggle-fluid-item"><?= lang('collapse') ?></a>
                        <a href class="dropdown__link js-hide-all-fluid-items"><?= lang('collapse_all') ?></a>
                        <a href class="dropdown__link js-show-all-fluid-items"><?= lang('expand_all') ?></a>
                        <div class="dropdown__divider"></div>
                        <a href class="dropdown__link dropdown__link--danger js-fluid-remove"><i class="fas fa-fw fa-trash-alt"></i> <?= lang('delete') ?></a>
                    </div>
                <?php else : ?>
                    <button type="button" class="fluid__item-tool js-fluid-remove danger-link" title="<?= lang('remove') ?>"><i class="fas fa-fw fa-trash-alt"></i></button>
                <?php endif; ?>

                <?php if (empty($is_bulk_edit) and isset($field_filters)) : ?>
                    <button type="button" data-dropdown-pos="bottom-end" class="fluid__item-tool js-dropdown-toggle" title="<?= lang('add_field') ?>"><i class="fas fa-fw fa-plus"></i></button>
                    <div class="dropdown">
                        <?php foreach ($field_filters as $filter) : ?>
                            <a href="#" class="dropdown__link" data-field-name="<?= $filter->name ?>"><img src="<?= $filter->icon ?>" width="12" height="12" />
                                <?= $filter->label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="field-instruct">
                <label>
                    <i class="icon--reorder reorder"></i>
                    <?= $field_group->group_name ?> <?php if ($show_field_type) : ?><span class="faded">(group)</span><?php endif ?>
                </label>
                <em><?= $field_group->group_name ?></em>
            </div>
        </div>

        <?php foreach ($field_group_fields as $field) : ?>
            <?php if($field->getType() !== 'fluid_field'): ?>
            <div class="fluid__item-field no-drag <?php if ($field->getType() == 'grid' || $field->getType() == 'file_grid') {
                                                        echo 'fieldset-faux';
                                                    } ?>" data-field-type="<?= $field->getType() ?>">

                <div class="field-instruct">
                    <label><?= $field->getItem('field_label') ?></label>
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

    <div class="fluid__item-tools fluid__item-tools--item-closed hidden">
        <button type="button" class="fluid__item-tool js-toggle-fluid-item" title="<?= lang('expand') ?>"><i class="fas fa-fw fa-angle-double-down"></i></button>
    </div>
</div>