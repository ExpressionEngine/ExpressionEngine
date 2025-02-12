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
		            'text' => lang('delete'),
		            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-remove"'
		        ]
		    ],
		    'modal' => true
		]); ?>


	     <?php endif; ?>
	<?=form_close();?>
</div>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => ee('CP/URL')->make('addons/settings/mailinglist/delete_user'),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
