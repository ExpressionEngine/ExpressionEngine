<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box snap mb table-list-wrap">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<div class="app-notice-wrap">
			<?=ee('CP/Alert')->get('view-members')?>
		</div>

		<div class="title-bar js-filters-collapsable">
			<h2 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>
			<?php if (isset($filters)) echo $filters; ?>
		</div>

		<?php $this->embed('_shared/table', $table); ?>

		<?php if ( ! empty($pagination)) echo $pagination; ?>

		<?php if ( ! empty($table['data']) && $can_delete_members): ?>
		<fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-delete"><?=lang('delete')?></option>
			</select>
			<button class="button button--primary" data-conditional-modal="confirm-trigger" data-confirm-ajax="<?=ee('CP/URL')->make('/members/confirm')?>"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
	</div>
</div>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-delete',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	),
	'secure_form_ctrls' => isset($confirm_remove_secure_form_ctrls) ? $confirm_remove_secure_form_ctrls : NULL
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
