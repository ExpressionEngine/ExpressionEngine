<div class="box">
	<?=form_open($base_url, 'class="tbl-ctrls"')?>
		<fieldset class="tbl-search right">
			<div class="filters">
				<ul>
					<li>
						<a class="has-sub" href=""><?=lang('create_new')?></a>
						<div class="sub-menu">
							<fieldset class="filter-search">
								<input type="text" value="" placeholder="<?=lang('filter_channels')?>">
							</fieldset>
							<ul>
								<?php foreach (ee()->menu->generate_menu()['channels']['create'] as $channel_name => $link): ?>
									<li><a href="<?=$link?>"><?=$channel_name?></a></li>
								<?php endforeach ?>
							</ul>
						</div>
					</li>
				</ul>
			</div>
		</fieldset>
		<h1><?=lang('all_pages')?></h1>

		<?=ee('Alert')->get('pages-form')?>

		<?php $this->ee_view('_shared/table', $table); ?>
		<?php $this->ee_view('_shared/pagination'); ?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
		<?php endif; ?>
	<?=form_close();?>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> cp_url('addons/settings/pages'),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>