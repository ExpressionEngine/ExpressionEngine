<div class="panel">
  <div class="tbl-ctrls">
		<?=form_open($form_url)?>
      <div class="panel-heading">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
        <div class="form-btns form-btns-top">
          <div class="title-bar title-bar--large">
      			<h3 class="title-bar__title"><?=$cp_heading?><br><i><?=$cp_heading_desc?></i></h3>
            <div class="title-bar__extra-tools">
              <a class="button button--primary" href="<?=$new_url?>"><?=lang('create_new')?></a>
            </div>
      			<?php if (isset($filters)) {
    echo $filters;
} ?>
          </div>
        </div>
      </div>
			<?php $this->embed('ee:_shared/table', $table); ?>
			<?=$pagination?>
			<?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php $this->embed('ee:_shared/form/bulk-action-bar', [
			    'options' => [
			        [
			            'value' => "",
			            'text' => '-- ' . lang('with_selected') . ' --'
			        ],
			        [
			            'value' => "remove",
			            'text' => lang('delete'),
			            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-remove-admin"'
			        ]
			    ],
			    'modal' => true
			]); ?>
			<?php endif; ?>
		<?=form_close()?>
	</div>
</div>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-remove-admin',
    'form_url' => $form_url,
    'hidden' => array(
        'return' => ee('CP/URL')->getCurrentUrl()->encode(),
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove-admin', $modal);
?>
