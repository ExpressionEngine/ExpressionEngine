<?php
$this->extend('_templates/default-nav-table');

$this->embed('publish/partials/edit_list_table');

$modal_vars = array(
	'name'		=> 'modal-confirm-remove-entry',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove-entry', $modal);

$modal = $this->make('ee:_shared/modal-bulk-edit')->render([
	'name' => 'modal-bulk-edit'
]);
ee('CP/Modal')->addModal('bulk-edit', $modal);

$modal = ee('View')->make('ee:_shared/modal-form')->render([
	'name' => 'modal-form',
	'contents' => ''
]);
ee('CP/Modal')->addModal('modal-form', $modal);
?>
