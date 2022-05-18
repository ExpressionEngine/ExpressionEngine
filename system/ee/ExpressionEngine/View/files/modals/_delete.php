<?php ee('CP/Modal')->startModal('view-file'); ?>

<div class="modal-wrap modal-view-file hidden">
	<div class="modal modal--no-padding">
		<a class="m-close" href="#"></a>
		<div class="box">
		</div>
	</div>
</div>

<?php ee('CP/Modal')->endModal(); ?>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-delete-file',
    'form_url' => $form_url,
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete-file', $modal);
?>
