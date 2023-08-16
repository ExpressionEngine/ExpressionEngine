<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
	<?=form_open($form_url)?>
    <div class="panel-heading">
      <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
      <div class="form-btns form-btns-top">
    		<div class="title-bar">
    			<h3 class="title-bar__title"><?=$cp_heading?></h3>
          <div class="title-bar__extra-tools">
    				<?php if ($show_new_template_button): ?>
    				<a class="button button--primary" href="<?=ee('CP/URL')->make('design/template/create/' . $group_id)?>"><?= lang('create_new_template') ?></a>
    			<?php endif; ?>
    			</div>
    			<?php if (isset($filters)) {
    echo $filters;
} ?>
    		</div>
      </div>
    </div>

		<?php $this->embed('_shared/table', $table); ?>
		<?php if (isset($pagination)) {
    echo $pagination;
} ?>
		<?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php
                $options = [
                    [
                        'value' => "",
                        'text' => '-- ' . lang('with_selected') . ' --'
                    ]
                ];
                if ($show_bulk_delete) {
                    $options[] = [
                        'value' => "remove",
                        'text' => lang('delete'),
                        'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete-template"'
                    ];
                }
                $this->embed('ee:_shared/form/bulk-action-bar', [
                    'options' => $options,
                    'modal' => true
                ]);
            ?>
		<?php endif; ?>
	<?=form_close()?>
</div>

<?php ee('CP/Modal')->startModal('template-settings'); ?>

<div class="modal-wrap modal-template-settings hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"><span class="sr-only"><?=lang('close_modal')?></span></a>
				<div class="box">
				</div>
			</div>
		</div>
	</div>
</div>

<?php ee('CP/Modal')->endModal(); ?>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-delete-template',
    'form_url' => $form_url,
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete-template', $modal);
?>
