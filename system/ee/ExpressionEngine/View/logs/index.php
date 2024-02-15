<?php
if (! AJAX_REQUEST) {
    $this->extend('_templates/default-nav', array(), 'outer_box');
}
?>

<div class="box panel">
    <div class="tbl-ctrls member_manager-wrapper">
        <?=form_open($table['base_url'], ['data-save-default-url' => ee('CP/URL')->make('logs/views/save-default', ['channel' => $channel])->compile()])?>
            <div class="panel-heading">
                <div class="title-bar">
                    <h3 class="title-bar__title title-bar--large"><?=$cp_heading?></h3>

                    <?php $this->embed('ee:_shared/title-toolbar', $toolbar_items); ?>

                </div>
            </div>

            <div class="entry-pannel-notice-wrap">
                <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
            </div>

            <div class="filter-search-bar members--filter-search-bar">
                <!-- All filters (not including search input) are contained within 'filter-search-bar__filter-row' -->
                <div class="filter-search-bar__filter-row">
                    <?php if (isset($filters)) echo $filters; ?>
                    <?php if (isset($filters_search)) echo $filters_search; ?>
                </div>
            </div>

            <?php $this->embed('_shared/table', $table); ?>

            <?php if (! empty($pagination)) {
                echo $pagination;
            } ?>

            <?php 
            if (! empty($table['data'])) {
                $bulk_options = array_merge([
                    [
                        'value' => "",
                        'text' => '-- ' . lang('with_selected') . ' --'
                    ]
                ], $bulk_options);
                $this->embed('ee:_shared/form/bulk-action-bar', [
                    'options' => $bulk_options,
                    'modal' => true,
                    'ajax_url' => ee('CP/URL')->make('/members/confirm')
                ]);
            }
            ?>
        <?=form_close()?>
    </div>
</div>

<?php
// preview modals
foreach ($logs as $log) {
    ee('CP/Modal')->startModal('modal-log-' . $log['log_id']); ?>
        <div class="app-modal app-modal--center" rev="modal-log-<?=$log['log_id']?>">
            <div class="app-modal__content">
                <div class="app-modal__dismiss">
                    <a class="js-modal-close" rel="modal-center" href="#"><?=lang('close_modal')?></a> <span class="txt-fade">[esc]</span>
                </div>
                <div class="md-wrap">
                    <p><?=lang('date') . ': ' . $log['log_date']?></p>
                    <p><?=lang('log_channel') . ': ' . $log['channel']?></p>
                    <p><?=lang('log_level') . ': ' . $log['level']?></p>
                    <p><?=lang('site_id') . ': ' . $log['site_id']?></p>
                    <p><?=lang('ip_address') . ': ' . $log['ip_address']?></p>
                    <p><strong><?=$log['message']?></strong></p>
                    <p><?=$log['context']?></p>
                    <p><?=$log['extra']?></p>
                </div>
            </div>
        </div>
    <?php ee('CP/Modal')->endModal();
}

// delete modal
$modal_vars = array(
    'name' => 'modal-confirm-delete',
    'form_url' => $form_url,
    'hidden' => array(
        'bulk_action' => 'remove'
    ),
    'secure_form_ctrls' => isset($confirm_remove_secure_form_ctrls) ? $confirm_remove_secure_form_ctrls : null
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);

// delete all modal
$modal_vars = array(
    'name' => 'modal-confirm-delete-all',
    'form_url' => $form_url,
    'hidden' => array(
        'bulk_action' => 'remove',
    ),
    'remove_confirmation' => form_hidden('selection'),
    'secure_form_ctrls' => null
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete-all', $modal);
?>
