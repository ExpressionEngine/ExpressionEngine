<?php if (! $permissions['admin']) {
    $this->functions->redirect(ee('CP/URL')->make('addons/settings/structure/index'));
}?>
<div class="padder structure-gui">

    <?php echo ee('CP/Alert')->getAllInlines(); ?>

<?=form_open($action_url, $attributes)?>

<?php if ($extension_is_installed === true) :?>
<div class="panel">
    <div class="panel-heading">
        <div class="title-bar title-bar--large">
            <h3 class="title-bar__title"><?=lang('cp_module_settings_title')?></h3>
        </div>
    </div>

    <?php $this->embed('ee:_shared/table', $module_settings); ?>
</div>
<?php endif;?>

<div class="panel">
    <div class="panel-heading">
        <div class="title-bar title-bar--large">
            <h3 class="title-bar__title"><?=lang('structure_tree_display_settings')?></h3>
        </div>
    </div>

    <?php $this->embed('ee:_shared/table', $display_settings); ?>
</div>

<div class="panel">
    <div class="panel-heading">
        <div class="title-bar title-bar--large">
            <h3 class="title-bar__title"><?=lang('member_group_permission')?></h3>
        </div>
    </div>

    <?php $this->embed('ee:_shared/table', $member_permissions); ?>
</div>

<?php $this->embed('ee:_shared/form/buttons'); ?>

</form>
</div> <!-- close .padder -->