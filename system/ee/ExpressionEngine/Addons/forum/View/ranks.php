	<div class="tbl-ctrls">
		<?=form_open($form_url)?>
			<fieldset class="tbl-search right">
				<a class="btn tn action" href="<?=ee('CP/URL')->make('addons/settings/forum/create/rank/')?>"><?=lang('create_new')?></a>
			</fieldset>
			<h1><?=$cp_heading?></h1>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
			<?php if (isset($filters)) echo $filters; ?>
			<?php $this->embed('ee:_shared/table', $table); ?>
			<?=$pagination?>
			<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php $this->embed('ee:_shared/form/bulk-action-bar', [
				'options' => [
					[
						'value' => "",
						'text' => '-- ' . lang('with_selected') . ' --'
					],
					[
						'value' => "remove",
						'text' => lang('delete'),
						'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-remove-rank"'
					]
				],
				'modal' => true
			]); ?>
			<?php endif; ?>
		<?=form_close()?>
	</div>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove-rank',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove-rank', $modal);
?>
