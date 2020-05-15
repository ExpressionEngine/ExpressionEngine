<?php $this->extend('_templates/default-nav'); ?>

	<?=form_open($table['base_url'])?>
		<fieldset class="tbl-search right">
			<a class="btn tn action" href="<?=ee('CP/URL')->make('files/watermarks/create')?>"><?=lang('create_new')?></a>
		</fieldset>

		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_page_title?></h2>
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
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('files/watermarks/remove', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
