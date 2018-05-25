<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

<div class="box table-list-wrap">
	<div class="tbl-ctrls">
		<?=form_open($base_url)?>
			<fieldset class="tbl-search right">
				<a class="btn action" href="<?=$create_url?>"><?=lang('new')?></a>
			</fieldset>
			<h1><?=$heading['user']?></h1>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->get('user-alerts')?></div>
			<?php $this->embed('_shared/table-list', ['data' => $requests['user'], 'filters' => $filters['user']]); ?>
			<fieldset class="tbl-bulk-act hidden">
				<select name="bulk_action">
					<option>-- <?=lang('with_selected')?> --</option>
					<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
				</select>
				<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
			</fieldset>
		</form>
	</div>
</div>

<?php if ( ! empty($requests['app'])) : ?>
<div class="box table-list-wrap">
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
</div>
<?php endif; ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('settings/consents', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
