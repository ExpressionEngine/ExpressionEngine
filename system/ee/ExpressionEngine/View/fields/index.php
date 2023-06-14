<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

<div class="panel">
  <div class="tbl-ctrls">
        <?=form_open($base_url)?>
    <div class="panel-heading">

      <div class="form-btns form-btns-top">
        <div class="title-bar title-bar--large">
          <h3 class="title-bar__title">
            <?=$cp_page_title?>
            <br>
            <i><?=$group_tag?></i>
          </h3>
        </div>

        
      </div>
    </div>
    <div class="panel-body">
    <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
            <?php $this->embed('_shared/table-list', ['data' => $fields]); ?>
            <?php if (isset($pagination)) {
    echo $pagination;
} ?>
            <?php $this->embed('ee:_shared/form/bulk-action-bar', [
                'options' => [
                    [
                        'value' => "",
                        'text' => '-- ' . lang('with_selected') . ' --'
                    ],
                    [
                        'value' => "remove",
                        'text' => lang('delete'),
                        'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete"'
                    ]
                ],
                'modal' => true
            ]); ?>
    </div>
        </form>
    </div>
</div>

<?php

$modal_vars = array(
    'name' => 'modal-confirm-delete',
    'form_url' => ee('CP/URL')->make('fields', ee()->cp->get_url_state()),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
