<?php $this->extend('_templates/default-nav'); ?>

<?=form_open($table['base_url'])?>
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

	<div class="title-bar">
		<h2 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>
		<?php if (isset($filters)) echo $filters; ?>
		<div class="title-bar__extra-tools">
			<?php if (ee()->cp->allowed_group('can_create_member_groups')): ?>
				<a class="button button--action button--small" href="<?=ee('CP/URL')->make('members/groups/create')?>"><?= lang('create_new') ?></a>
			<?php endif; ?>
		</div>
	</div>

	<?php $this->embed('_shared/table', $table); ?>

	<?php if ( ! empty($pagination)) echo $pagination; ?>

	<?php if ( ! empty($table['data'])): ?>
	<fieldset class="bulk-action-bar hidden">
		<select name="bulk_action">
			<option value="">-- <?=lang('with_selected')?> --</option>
			<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-delete"><?=lang('delete')?></option>
		</select>
		<button class="button button--primary" data-conditional-modal="confirm-trigger" data-confirm-ajax="<?=ee('CP/URL')->make('/members/groups/confirm')?>"><?=lang('submit')?></button>
	</fieldset>
	<?php endif; ?>
<?=form_close()?>

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
