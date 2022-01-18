<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
    <?=form_open(ee('CP/URL')->make('utilities/stats/sync'))?>

    <div class="panel-heading">
        <div class="title-bar">
            <h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
        </div>
    </div>

    <div class="panel-body">

        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

        <?php $this->embed('ee:_shared/table', $table); ?>

        <?php $this->embed('ee:_shared/form/bulk-action-bar', [
            'options' => [
                [
                    'value' => "",
                    'text' => '-- ' . lang('with_selected') . ' --'
                ],
                [
                    'value' => "sync",
                    'text' => lang('sync')
                ]
            ]
        ]); ?>

    </div>

    <?=form_close()?>
</div>
