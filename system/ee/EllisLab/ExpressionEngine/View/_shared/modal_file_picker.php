<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<?php if (is_numeric($dir)): ?>
	<fieldset class="tbl-search right">
		<a class="btn tn action" href="<?=ee('CP/URL')->make("files/upload/$dir")?>">Upload New File</a>
	</fieldset>
	<?php endif ?>
	<h1>
		<?php if (is_numeric($dir)): ?>
		<ul class="toolbar">
			<li class="sync">
				<a href="<?=ee('CP/URL')->make("settings/upload/sync/$dir")?>" title="<?=lang('sync_directories')?>"></a>
			</li>
		</ul>
		<?php endif ?>
		<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
	</h1>

	<?=ee('CP/Alert')->getAllInlines()?>

	<?php if (isset($filters)) echo $filters; ?>

	<?php $this->embed('_shared/table', $table); ?>

	<?php if ( ! empty($pagination)) $this->embed('_shared/pagination', $pagination); ?>

	<?php if ( ! empty($table['data'])): ?>
	<fieldset class="tbl-bulk-act hidden">
		<select name="bulk_action">
			<option value="">-- <?=lang('with_selected')?> --</option>
			<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
		</select>
		<button class="btn submit" data-conditional-modal="confirm-trigger" data-confirm-ajax="<?=ee('CP/URL')->make('/members/confirm')?>"><?=lang('submit')?></button>
	</fieldset>
	<?php endif; ?>
<?=form_close()?>
</div>
