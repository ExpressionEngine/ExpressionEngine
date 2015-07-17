<?php $this->extend('_templates/default-nav'); ?>

<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$table['search']?>">
		<input class="btn submit" type="submit" value="<?=lang('search_subscriptions')?>">
	</fieldset>
	<h1>
		<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
	</h1>

	<?php if (isset($filters)) echo $filters; ?>

	<?=ee('Alert')->getAllInlines()?>

	<?php $this->embed('_shared/table', $table); ?>

	<?php if ( ! empty($pagination)) $this->embed('_shared/pagination', $pagination); ?>

	<?php if ( ! empty($table['data'])): ?>
	<fieldset class="tbl-bulk-act">
		<select name="bulk_action">
			<option value="">-- <?=lang('with_selected')?> --</option>
			<option value="unsubscribe" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('unsubscribe')?></option>
		</select>
	   	<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
	</fieldset>
	<?php endif; ?>
<?=form_close()?>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> $table['base_url'],
	'hidden'	=> array(
		'bulk_action'	=> 'unsubscribe'
	)
);

$this->embed('ee:_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>
