<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<h1><?=$cp_heading?></h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php $this->embed('_shared/table', $table); ?>
		<? if (isset($pagination)) echo $pagination; ?>
		<?php if ($table['total_rows'] > 0): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="disable"><?=lang('disable')?></option>
				<option value="enable"><?=lang('enable')?></option>
			</select>
			<input class="btn submit" type="submit" value="<?=lang('submit')?>">
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
</div>
