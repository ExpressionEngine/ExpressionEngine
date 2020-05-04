<?php $this->extend('_templates/default-nav'); ?>

	<?=form_open($form_url)?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_heading?></h2>
		</div>

		<?php $this->embed('_shared/table', $table); ?>
		<? if (isset($pagination)) echo $pagination; ?>
		<?php if ($table['total_rows'] > 0): ?>
		<fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="disable"><?=lang('disable')?></option>
				<option value="enable"><?=lang('enable')?></option>
			</select>
			<input class="button button--primary" type="submit" value="<?=lang('submit')?>">
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
