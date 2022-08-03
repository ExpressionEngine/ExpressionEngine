<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel">
    <div class="panel-body">
        <div class="tbl-ctrls">
            <?=form_open($table['base_url'])?>
                <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

                <div class="title-bar">
                    <h2 class="title-bar__title"><?=$table_heading?></h2>
                </div>

                <?php $this->embed('_shared/table', $table); ?>
                <?php $this->embed('ee:_shared/form/bulk-action-bar', [
                    'options' => [
                        [
                            'value' => "SYNC",
                            'text' => lang('sync_channel_entries')
                        ],
                    ]
                ]); ?>
            </form>
        </div>
    </div>
</div>
