<div class="scroll-wrap">
	<ul class="folder-list<?php if ($can_reorder): ?> reorderable<?php endif ?>" <?php if ($can_reorder): ?> data-name="<?=$name?>"<?php endif ?>>
		<?=$items?>
	</ul>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-' . $name,
	'form_url'	=> $remove_url,
	'hidden'	=> array(
		$removal_key => ''
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal($name, $modal);
?>
