<?php extend_view('account/_wrapper') ?>

<div>
	<?=form_open($action, '', $form_hidden)?>
		<?=$content?>
	<?=form_close()?>
</div>