<div class="scroll-wrap">
	<ul class="folder-list">
		<?=$items?>
	</ul>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-<?=$name?>',
	'form_url'	=> $remove_url,
	'hidden'	=> array(
		$removal_key => ''
	)
);

$this->embed('ee:_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>
