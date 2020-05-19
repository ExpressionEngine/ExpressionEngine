<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

	<div class="tbl-ctrls">
		<?php if (empty($layouts) && empty($channel_id)): ?>
			<?php $this->embed('_shared/table-list', ['data' => []]); ?>
		<?php else: ?>
			<?=form_open($base_url)?>
				<fieldset class="tbl-search right">
					<a class="btn action" href="<?=$create_url?>"><?=lang('new_layout')?></a>
				</fieldset>
				<h1><?=$cp_page_title?></h1>
				<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
				<?php $this->embed('_shared/table-list', ['data' => $layouts]); ?>
				<?php if (isset($pagination)) echo $pagination; ?>
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
		<?php endif?>
	</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-delete',
	'form_url'	=> ee('CP/URL')->make('channels/layouts/' . $channel_id, ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
