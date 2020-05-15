<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

	<div class="tbl-ctrls">
		<?=form_open($base_url)?>
			<fieldset class="tbl-search right">
				<a class="btn action" href="<?=$create_url?>"><?=lang('new')?></a>
			</fieldset>
			<h1><?=$heading['user']?></h1>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->get('user-alerts')?></div>
			<?php $this->embed('_shared/table-list', ['data' => $requests['user'], 'filters' => $filters['user']]); ?>
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
	</div>

<?php if ( ! empty($requests['app'])) : ?>
	<div class="tbl-ctrls">
		<?=form_open($base_url)?>
			<h1><?=$heading['app']?></h1>
			<div class="app-notice-wrap">
				<?=ee('CP/Alert')->get('app-alerts')?>
				<?=ee('CP/Alert')->get('no-cookie-consent')?>
			</div>
			<?php $this->embed('_shared/table-list', ['data' => $requests['app'], 'filters' => $filters['app']]); ?>
		</form>
	</div>
<?php endif; ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-delete',
	'form_url'	=> ee('CP/URL')->make('settings/consents', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
