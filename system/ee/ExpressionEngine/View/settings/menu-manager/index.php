<?php $this->extend('_templates/default-nav'); ?>

	<?=form_open($table['base_url'])?>

		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title">
				<?=$cp_page_title?>
			</h2>

			<div class="title-bar__extra-tools">
				<a class="button button--small button--action" href="<?=ee('CP/URL')->make('settings/menu-manager/create-set')?>"><?=lang('new')?></a>
			</div>
		</div>


		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
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
			'modal' => true
		]); ?>
	</form>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-delete',
	'form_url'	=> ee('CP/URL')->make('settings/menu-manager/remove-set', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
