<?php
if (! AJAX_REQUEST) {
    $this->extend('_templates/default-nav', array(), 'outer_box');
}
?>

<div class="box panel">
    <div class="tbl-ctrls member_manager-wrapper">
        <?=form_open($table['base_url'], ['data-save-default-url' => ee('CP/URL')->make('members/views/save-default', ['role_id' => $role_id])->compile()])?>
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

$modal_vars = array(
    'name' => 'modal-confirm-decline',
    'title' => lang('confirm_decline'),
    'alert' => lang('confirm_decline_desc'),
    'button' => array(
        'text' => 'btn_confirm_and_decline',
        'working' => 'btn_confirm_and_decline_working'
    ),
    'form_url' => $form_url,
    'hidden' => array(
        'bulk_action' => 'decline'
    ),
    'secure_form_ctrls' => false
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('decline', $modal);
?>
