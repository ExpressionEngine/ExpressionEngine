<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
		<input class="btn submit" type="submit" value="<?=lang('search_subscriptions')?>">
	</fieldset>
	<h1>
		<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
	</h1>

	<?php if (isset($filters)) echo $filters; ?>

	<?=ee('CP/Alert')->getAllInlines()?>

	<?php $this->embed('_shared/table', $table); ?>

	<?php if ( ! empty($pagination)) $this->embed('_shared/pagination', $pagination); ?>

	<?php if ( ! empty($table['data'])): ?>
	<fieldset class="tbl-bulk-act hidden">
		<select name="bulk_action">
			<option value="">-- <?=lang('with_selected')?> --</option>
			<option value="unsubscribe" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('unsubscribe')?></option>
		</select>
	   	<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
	</fieldset>
	<?php endif; ?>
<?=form_close()?>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> $table['base_url'],
	'hidden'	=> array(
		'bulk_action'	=> 'unsubscribe'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
