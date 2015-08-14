<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box mb">
	<div class="tbl-ctrls">
		<?=form_open($form_url)?>
			<h1><?=$cp_heading['first']?></h1>
			<?=ee('Alert')->get('first-party')?>
			<?php if (isset($filters['first'])) echo $filters['first']; ?>
			<?php $this->embed('_shared/table', $tables['first']); ?>
			<?php if ( ! empty($tables['first']['columns']) && ! empty($tables['first']['data'])): ?>
			<fieldset class="tbl-bulk-act hidden">
				<select name="bulk_action">
					<option value="">-- <?=lang('with_selected')?> --</option>
					<option value="install"><?=lang('install')?></option>
					<option value="remove" data-confirm-trigger-first="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
					<option value="update"><?=lang('update')?></option>
				</select>
				<button class="btn submit" data-conditional-modal="confirm-trigger-first"><?=lang('submit')?></button>
			</fieldset>
			<?php endif; ?>
		<?=form_close()?>
	</div>
</div>
<?php if (isset($tables['third'])): ?>
<div class="box">
	<div class="tbl-ctrls">
		<?=form_open($form_url)?>
			<h1><?=$cp_heading['third']?></h1>
			<?=ee('Alert')->get('third-party')?>
			<?php if (isset($filters['third'])) echo $filters['third']; ?>
			<?php $this->embed('_shared/table', $tables['third']); ?>
			<?php if ( ! empty($tables['third']['columns']) && ! empty($tables['third']['data'])): ?>
			<fieldset class="tbl-bulk-act hidden">
				<select name="bulk_action">
					<option value="">-- <?=lang('with_selected')?> --</option>
					<option value="install"><?=lang('install')?></option>
					<option value="remove" data-confirm-trigger-third="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
					<option value="update"><?=lang('update')?></option>
				</select>
				<button class="btn submit" data-conditional-modal="confirm-trigger-third"><?=lang('submit')?></button>
			</fieldset>
			<?php endif; ?>
		<?=form_close()?>
	</div>
</div>
<?php endif; ?>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>
<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->embed('ee:_shared/modal_confirm_remove', $modal_vars);
?>
