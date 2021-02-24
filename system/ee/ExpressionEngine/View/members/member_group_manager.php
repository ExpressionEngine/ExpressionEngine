<?php $this->extend('_templates/default-nav'); ?>

<?=form_open($table['base_url'])?>
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

	<div class="title-bar">
		<h2 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>
		<?php if (isset($filters)) {
    echo $filters;
} ?>
		<div class="title-bar__extra-tools">
			<?php if (ee('Permission')->has('can_create_member_groups')): ?>
				<a class="button button--primary button--small" href="<?=ee('CP/URL')->make('members/groups/create')?>"><?= lang('create_new') ?></a>
			<?php endif; ?>
		</div>
	</div>

	<?php $this->embed('_shared/table', $table); ?>

	<?php if (! empty($pagination)) {
    echo $pagination;
} ?>

	<?php if (! empty($table['data'])): ?>
	<?php $this->embed('ee:_shared/form/bulk-action-bar', [
	    'options' => [
	        [
	            'value' => "",
	            'text' => '-- ' . lang('with_selected') . ' --'
	        ],
	        [
	            'value' => "remove",
	            'text' => lang('delete'),
	            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete"'
	        ]
	    ],
	    'modal' => true,
	    'ajax_url' => ee('CP/URL')->make('/members/groups/confirm')
	]); ?>
	<?php endif; ?>
<?=form_close()?>

<?php

$modal_vars = array(
    'name' => 'modal-confirm-delete',
    'form_url' => $form_url,
    'hidden' => array(
        'bulk_action' => 'remove'
    ),
    'secure_form_ctrls' => isset($confirm_remove_secure_form_ctrls) ? $confirm_remove_secure_form_ctrls : null
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
