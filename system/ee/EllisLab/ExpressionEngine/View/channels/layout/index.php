<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

<div class="box table-list-wrap">
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
				<fieldset class="tbl-bulk-act hidden">
					<select name="bulk_action">
						<option>-- <?=lang('with_selected')?> --</option>
						<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
					</select>
					<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
				</fieldset>
			</form>
		<?php endif?>
	</div>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('channels/layouts/' . $channel_id, ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
