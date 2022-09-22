<div class="fields-grid-item fields-grid-item---open" data-field-name="">
    <div class="fields-grid-tools">
        <a class="fields-grid-tool-expand" href="" title="<?=lang('grid_expand_field')?>">Overwrite existing</a>
        <a class="fields-grid-tool-expand hidden" href="" title="<?=lang('grid_reorder_field')?>">Edit</a>
        <a class="fields-grid-tool-remove" href="" title="<?=lang('grid_remove_field')?>">Skip importing</a>
    </div>
    <div class="toggle-content">
        <div class="fields-grid-common">
            <?=$this->embed('_shared/form/section', ['name' => $name, 'settings' => $fields])?>
        </div>
    </div>
</div>
