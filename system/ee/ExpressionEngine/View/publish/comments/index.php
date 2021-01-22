<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
	<?=form_open($form_url)?>
  <div class="panel-heading">
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar js-filters-collapsible">
			<h3 class="title-bar__title">
				<?=$cp_heading?>
			</h3>

			<?php if (isset($filters)) {
    echo $filters;
} ?>
		</div>
  </div>

		<?php $this->embed('_shared/table', $table); ?>

		<?=$pagination?>

		<?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php if ($can_delete || $can_moderate) {
    $options = [
        [
            'value' => "",
            'text' => '-- ' . lang('with_selected') . ' --'
        ]
    ];
    if ($can_delete) {
        $options[] = [
            'value' => "remove",
            'text' => lang('delete'),
            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete-comment"'
        ];
    }
    if ($can_moderate) {
        $options[] = [
            'value' => "open",
            'text' => lang('set_to_open')
        ];
        $options[] = [
            'value' => "closed",
            'text' => lang('set_to_closed')
        ];
        $options[] = [
            'value' => "pending",
            'text' => lang('set_to_pending')
        ];
    }
    $this->embed('ee:_shared/form/bulk-action-bar', [
        'options' => $options,
        'modal' => true
    ]);
}
            ?>
		<?php endif; ?>
	<?=form_close()?>
</div>
<?php
$modal_vars = array(
    'name' => 'modal-confirm-delete-comment',
    'form_url' => $form_url,
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete-comment', $modal);
?>
