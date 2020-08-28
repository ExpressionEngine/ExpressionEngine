<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box snap mb table-list-wrap">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<div class="app-notice-wrap">
			<?=ee('CP/Alert')->get('view-members')?>
		</div>

		<div class="title-bar js-filters-collapsible">
			<h2 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>
			<?php if (isset($filters)) echo $filters; ?>
		</div>

		<?php $this->embed('_shared/table', $table); ?>

		<?php if ( ! empty($pagination)) echo $pagination; ?>

		<?php if ( ! empty($table['data']) && $can_delete_members): ?>
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
			'ajax_url' => ee('CP/URL')->make('/members/confirm')
		]); ?>
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
