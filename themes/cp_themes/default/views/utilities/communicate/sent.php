<?php extend_template('default-nav'); ?>

<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$table['search']?>">
		<input class="btn submit" type="submit" value="<?=lang('search_emails_button')?>">
	</fieldset>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<?php $this->view('_shared/alerts')?>
	<?php $this->view('_shared/table', $table); ?>

	<?php $this->view('_shared/pagination'); ?>

<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
	<fieldset class="tbl-bulk-act">
		<select name="bulk_action">
			<option value="">-- <?=lang('with_selected')?> --</option>
			<option value="remove"><?=lang('remove')?></option>
		</select>
		<button class="btn submit" rel="modal-confirm-all"><?=lang('submit')?></button>
	</fieldset>
<?php endif; ?>
<?=form_close()?>
</div>