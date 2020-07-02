<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

<div class="box table-list-wrap">
	<div class="tbl-ctrls">
		<?=form_open($base_url)?>

			<div class="app-notice-wrap">
				<?=ee('CP/Alert')->getAllInlines()?>
			</div>

			<div class="title-bar">
				<h2 class="title-bar__title"><?=$cp_page_title?></h2>
			</div>

			<?php $this->embed('_shared/table-list', ['data' => $roles]); ?>
			<?php if (isset($pagination)) echo $pagination; ?>

			<?php if (!isset($disable_action) || empty($disable_action)) : ?>
			<?php $this->embed('ee:_shared/form/bulk-action-bar', [
				'options' => [
					[
						'value' => "",
						'text' => '-- ' . lang('with_selected') . ' --'
					],
					[
						'value' => "remove",
						'text' => lang('delete'),
						'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete" '
					]
				],
				'modal' => true,
				'ajax_url' => ee('CP/URL')->make('/members/roles/confirm')
			]); ?>
			<?php endif; ?>
		</form>
	</div>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-delete',
	'form_url'	=> ee('CP/URL')->make('members/roles', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
