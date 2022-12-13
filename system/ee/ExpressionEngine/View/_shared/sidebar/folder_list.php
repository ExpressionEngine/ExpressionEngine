<div class="scroll-wrap">
	<div class="folder-list<?php if ($can_reorder): ?> reorderable<?php endif ?>" <?php if ($can_reorder): ?> data-name="<?=$name?>"<?php endif ?>>
		<?=$items?>
	</div>
</div>

<?php

$modal_vars = array(
    'name' => 'modal-confirm-' . $name,
    'form_url' => $remove_url,
    'remove_confirmation' => isset($remove_confirmation) ? $remove_confirmation : '',
    'hidden' => array(
        $removal_key => ''
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal($name, $modal);
?>
