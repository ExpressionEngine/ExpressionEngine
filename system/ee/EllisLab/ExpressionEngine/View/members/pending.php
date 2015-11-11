<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box snap mb">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<h1>
			<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
		</h1>

		<?=ee('CP/Alert')->get('view-members')?>

		<?php $this->embed('_shared/table', $table); ?>

		<?php if ( ! empty($pagination)) echo $pagination; ?>

		<?php if ( ! empty($table['data']) && $can_delete_members): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<?php if ($can_edit): ?>
				<option value="approve"><?=lang('approve')?></option>
				<option value="resend"><?=lang('resend')?></option>
				<?php endif; ?>
				<?php if ($can_delete): ?>
				<option value="decline" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('decline')?></option>
				<?php endif; ?>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
	</div>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'decline'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
