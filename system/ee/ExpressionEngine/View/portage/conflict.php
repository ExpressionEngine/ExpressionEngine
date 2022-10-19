<div class="fields-grid-item fields-grid-item---open grid-item-conflict" data-field-name="">
    <input type="hidden" name="<?=$baseKey?>[portage__action]" value="" >
    <?php if ($duplicates !== false) : ?>
        <input type="hidden" name="<?=$baseKey?>[portage__duplicates]" value="<?=$duplicates?>" >
    <?php endif; ?>
    <div class="fields-grid-tools">
        <?php if ($duplicates !== false && $forbid_overwrite == false) : ?>
        <a class="fields-grid-tool-overwrite js-grid-tool-overwrite" href="" title="<?=lang('portage_overwrite_field')?>"><span class="sr-only"><?=lang('portage_overwrite_field')?></span></a>
        <?php endif; ?>
        <a class="fields-grid-tool-edit hidden js-grid-tool-edit" href="" title="<?=lang('portage_edit_field')?>"><span class="sr-only"><?=lang('portage_edit_field')?></span></a>
        <a class="fields-grid-tool-remove js-grid-tool-remove" href="" title="<?=lang('portage_skip_field')?>"><span class="sr-only"><?=lang('portage_skip_field')?></span></a>
    </div>

    <div class="toggle-content">
        <div class="fields-grid-common">
            <?=$this->embed('_shared/form/section', ['name' => $name, 'settings' => $fields])?>
        </div>
    </div>
</div>
<div class="alert alert--warning" style="display: none">
    <div class="alert__icon"><i class="fal fa-exclamation-circle fa-fw"></i></div>
    <div class="alert__content">
        <p class="alert__title"></p>
    </div>
</div>
