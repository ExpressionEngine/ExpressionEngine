<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls grid-publish">
	<?=form_open($form_url)?>
		<h1>
			<?=$cp_heading?><br>
			<i><?=$cp_sub_heading?></i>
		</h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php $this->embed('_shared/table', $table); ?>
		<?php $this->embed('_shared/pagination'); ?>
		<?php if ( ! empty($table['data'])): ?>
			<fieldset class="tbl-bulk-act">
				<input class="btn submit" type="submit" value="<?=lang('update')?>">
			</fieldset>
		<?php endif ?>
	<?=form_close()?>
</div>
