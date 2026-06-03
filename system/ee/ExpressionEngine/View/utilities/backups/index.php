<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>
<div class="box add-mrg-bottom">
    <?php echo ee('CP/Alert')->getAllInlines(); ?>
</div>

<div class="panel">
    <div class="panel-heading">
        <div class="title-bar">
            <h3 class="title-bar__title"><?=lang('backups')?></h3>

            <div class="title-bar__extra-tools">
                <a class="button button--primary" href="<?php echo ee('CP/URL')->make('utilities/db-backup/backup'); ?>"><?php echo lang('backup_database'); ?></a>
            </div>
        </div>
    </div>

<div class="box table-list-wrap">
    <?php echo form_open($base_url, 'class="tbl-ctrls"'); ?>
    <div class="app-notice-wrap">
        <?php echo ee('CP/Alert')->get('items-table'); ?>
    </div>

    <?php $this->embed('ee:_shared/table', $table); ?>
    <?php echo form_close(); ?>
</div>
</div>
