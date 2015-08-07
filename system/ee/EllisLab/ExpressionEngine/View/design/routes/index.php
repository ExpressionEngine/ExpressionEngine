<?php $this->extend('_templates/default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<h1><?=$cp_heading?></h1>
		<?=ee('Alert')->getAllInlines()?>
		<?php $this->embed('_shared/table', $table); ?>
		<?php $this->embed('_shared/pagination'); ?>
		<fieldset class="tbl-bulk-act">
			<button class="btn submit"><?=lang('submit')?></button>
		</fieldset>
	<?=form_close()?>
</div>
