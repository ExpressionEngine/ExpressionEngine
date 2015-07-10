<?php extend_template('default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<h1><?=$cp_heading?></h1>
		<?=ee('Alert')->getAllInlines()?>
		<?php $this->view('_shared/table', $table); ?>
		<?php $this->view('_shared/pagination'); ?>
		<fieldset class="tbl-bulk-act">
			<button class="btn submit"><?=lang('submit')?></button>
		</fieldset>
	<?=form_close()?>
</div>
