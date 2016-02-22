<?php $this->extend('_templates/default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<h1><?=$cp_heading?></h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php $this->embed('_shared/table', $table); ?>
		<?php $this->embed('_shared/pagination'); ?>
		<?php if ( ! empty($table['data'])): ?>
			<fieldset class="tbl-bulk-act">
				<button class="btn submit"><?=lang('update')?></button>
			</fieldset>
		<?php endif ?>
	<?=form_close()?>
</div>
