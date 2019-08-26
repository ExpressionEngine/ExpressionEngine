<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open(ee('CP/URL')->make('utilities/stats/sync'))?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>
		</div>

		<?php $this->embed('ee:_shared/table', $table); ?>

		<fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="sync"><?=lang('sync')?></option>
			</select>
			<input class="button button--primary" type="submit" value="<?=lang('submit')?>">
		</fieldset>
	<?=form_close()?>
</div>
