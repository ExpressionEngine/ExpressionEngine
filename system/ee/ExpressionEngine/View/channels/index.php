<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

<div class="panel">
  <div class="tbl-ctrls">
		<?=form_open($base_url)?>
      <div class="panel-body">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

  			<?php $this->embed('_shared/table-list', ['data' => $channels]); ?>
  			<?php if (isset($pagination)) {
    echo $pagination;
} ?>
  			<?php
            if (ee('Permission')->can('delete_channels')) {
                $options = [
                    [
                        'value' => "",
                        'text' => '-- ' . lang('with_selected') . ' --'
                    ]
                ];
                $options[] = [
                    'value' => "remove",
                    'text' => lang('delete'),
                    'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete"'
                ];
                $this->embed('ee:_shared/form/bulk-action-bar', [
                    'options' => $options,
                    'modal' => true
                ]);
            }
            ?>
      </div>
		</form>
	</div>
</div>

<?php

$modal_vars = array(
    'name' => 'modal-confirm-delete',
    'form_url' => ee('CP/URL')->make('channels', ee()->cp->get_url_state()),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
