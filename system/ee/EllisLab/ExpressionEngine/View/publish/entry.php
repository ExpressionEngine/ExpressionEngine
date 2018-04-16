<?php $this->extend('_templates/default-nav', array(), 'outer_box');

$this->embed('publish/partials/publish_form');

$modal = ee('View')->make('ee:_shared/modal-form')->render([
	'name' => 'modal-form',
	'contents' => ''
]);
ee('CP/Modal')->addModal('modal-form', $modal);
