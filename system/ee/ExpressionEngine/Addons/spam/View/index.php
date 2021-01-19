<div class="panel">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
    <div class="panel-heading">
  		<div class="title-bar">
  			<h3 class="title-bar__title">
  				<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></br>
  			</h3>

  			<?php if (isset($filters)) {
    echo $filters;
} ?>
  		</div>
    </div>

		<?= ee('View')->make('ee:_shared/table')->render($table); ?>

		<?php if (! empty($pagination)) {
    echo $pagination;
} ?>

		<?php if (! empty($table['data'])): ?>
		<?php $this->embed('ee:_shared/form/bulk-action-bar', [
		    'options' => [
		        [
		            'value' => "",
		            'text' => '-- ' . lang('with_selected') . ' --'
		        ],
		        [
		            'value' => "remove",
		            'text' => lang('deny_spam'),
		            'attrs' => ' rel="modal-confirm-remove"'
		        ],
		        [
		            'value' => "approve",
		            'text' => lang('approve_spam'),
		            'attrs' => ' class="yes" rel="modal-confirm-remove"'
		        ]
		    ]
		]); ?>
		<?php endif; ?>
	<?=form_close()?>
	</div>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => $form_url,
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$spam = ee('View')->make('spam:modal')->render();
ee('CP/Modal')->addModal('spam', $spam);
?>

<?php $this->endBlock(); ?>
