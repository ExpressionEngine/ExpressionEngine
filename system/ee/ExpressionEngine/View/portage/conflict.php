<div class="fields-grid-item fields-grid-item---open grid-item-conflict" data-field-name="">
    <input type="hidden" name="<?=$baseKey?>[portage__action]" value="" >
    <div class="fields-grid-tools">
        <a class="fields-grid-tool-overwrite js-grid-tool-overwrite" href="" title="<?=lang('grid_overwright_field')?>"><span class="sr-only">Overwrite row</span></a>
        <a class="fields-grid-tool-edit hidden js-grid-tool-edit" href="" title="<?=lang('grid_edit_field')?>"><span class="sr-only">Edit row</span></a>
        <a class="fields-grid-tool-remove js-grid-tool-remove" href="" title="<?=lang('grid_skip_field')?>"><span class="sr-only">Skip row</span></a>
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
    <a href="" class="alert__close">
        <i class="fal fa-times alert__close-icon"></i>
    </a>
</div>
