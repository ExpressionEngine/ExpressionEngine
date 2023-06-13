            <div class="fluid__item-tools fluid__item-tools--item-open">
                <a href class="fluid__item-tool js-toggle-fluid-item">
                    <span class="sr-only"><?=lang('collapse')?></span>
                    <i class="fal fa-caret-square-up fa-fw"></i>
                </a>

                <?php if (empty($is_bulk_edit)) : ?>
                    <button type="button" data-dropdown-offset="0px, -30px" data-dropdown-pos="bottom-end" class="fluid__item-tool js-dropdown-toggle"><i class="fal fa-fw fa-cog"></i></button>
                    <div class="dropdown">
                        <a href class="dropdown__link js-hide-all-fluid-items"><?= lang('collapse_all') ?></a>
                        <a href class="dropdown__link js-show-all-fluid-items"><?= lang('expand_all') ?></a>
                        <div class="dropdown__divider"></div>
                        <a href class="dropdown__link dropdown__link--danger js-fluid-remove"><i class="fal fa-fw fa-trash-alt"></i> <?= lang('delete') ?></a>
                    </div>
                <?php else : ?>
                    <button type="button" class="fluid__item-tool js-fluid-remove danger-link" title="<?= lang('remove') ?>"><i class="fal fa-fw fa-trash-alt"></i></button>
                <?php endif; ?>

                <?php if (empty($is_bulk_edit) and isset($field_filters)) : ?>
                    <button type="button" data-dropdown-pos="bottom-end" class="fluid__item-tool js-dropdown-toggle" title="<?= lang('add_field') ?>"><i class="fal fa-fw fa-plus"></i></button>
                    <div class="dropdown">
                        <?php foreach ($field_filters as $filter) : ?>
                            <a href="#" class="dropdown__link" data-field-name="<?= $filter->name ?>"><img src="<?= $filter->icon ?>" width="12" height="12" />&nbsp;<?= $filter->label ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="fluid__item-tools fluid__item-tools--item-closed hidden">
                <button type="button" class="fluid__item-tool js-toggle-fluid-item" title="<?=lang('expand')?>"><i class="fal fa-fw fa-angle-double-down"></i></button>
            </div>