<div class="box">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<fieldset class="tbl-search right">
			<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$table['search']?>">
			<input class="btn submit" type="submit" value="<?=lang('search_spam')?>">
		</fieldset>
		<h1>
			<ul class="toolbar">
				<li class="settings">
					<a href="<?=cp_url('addons/settings/spam/settings')?>" title="<?=lang('spam_settings')?>"></a>
				</li>
			</ul>
			<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
		</h1>
	
		<?=ee('Alert')->getAllInlines()?>
	
		<?php if (isset($filters)) echo $filters; ?>
	
		<?php $this->ee_view('_shared/table', $table); ?>
	
		<?php if ( ! empty($pagination)) $this->ee_view('_shared/pagination', $pagination); ?>
	
		<?php if ( ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger" data-confirm-ajax="<?=cp_url('/members/confirm')?>"><?=lang('submit')?></button>
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

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>
