<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel">

    <div class="panel-heading">
        <div class="title-bar">
            <h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
        </div>
    </div>

    <div class="panel-body">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
        <?php $this->embed('_shared/table', $table); ?>
    </div>

</div>


<?php
$modal_vars = array(
    'name' => 'modal-confirm-delete-template-group',
    'form_url' => $form_url,
    'hidden' => array(
        'bulk_action' => 'remove',
        'return' => 'utilities/debug-tools/duplicate-template-groups',
        'group_id' => ''
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete-template-group', $modal);
?>