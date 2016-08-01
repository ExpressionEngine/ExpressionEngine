<div class="box table-list-wrap">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<fieldset class="tbl-search right">
			<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
			<input class="btn submit" type="submit" value="<?=lang('search_spam')?>">
		</fieldset>
		<h1>
			<ul class="toolbar">
				<li class="settings">
					<a href="<?=ee('CP/URL', 'addons/settings/spam/settings')?>" title="<?=lang('spam_settings')?>"></a>
				</li>
			</ul>
			<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
		</h1>

		<?=ee('CP/Alert')->getAllInlines()?>

		<?php if (isset($filters)) echo $filters; ?>

		<?= ee('View')->make('ee:_shared/table')->render($table); ?>

		<?php if ( ! empty($pagination)) echo $pagination; ?>

		<?php if ( ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option value="">-- <?=lang('mark_selected')?> --</option>
				<option value="remove" rel="modal-confirm-remove"><?=lang('deny_spam')?></option>
				<option value="approve" class="yes" rel="modal-confirm-remove"><?=lang('approve_spam')?></option>
			</select>
			<button class="btn submit"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
	</div>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$spam = ee('View')->make('spam:modal')->render();
ee('CP/Modal')->addModal('spam', $spam);
?>

<?php $this->endBlock(); ?>
