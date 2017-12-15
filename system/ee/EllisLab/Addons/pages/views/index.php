<div class="box table-list-wrap">
	<?=form_open($base_url, 'class="tbl-ctrls"')?>
		<fieldset class="tbl-search right">
			<div class="filters">
				<ul>
					<li>
						<a class="has-sub" href=""><?=lang('create_new')?></a>
						<div class="sub-menu">
							<ul class="channels-pages-create">
								<?php
								$menus = ee()->menu->generate_menu();
								foreach ($menus['channels']['create'] as $channel_name => $link):
								?>
									<li class="search-channel" data-search="<?=strtolower($channel_name)?>"><a href="<?=$link?>"><?=$channel_name?></a></li>
								<?php endforeach ?>
							</ul>
						</div>
					</li>
				</ul>
			</div>
		</fieldset>
		<h1><?=lang('all_pages')?></h1>

		<?=ee('CP/Alert')->get('pages-form')?>

		<?php $this->embed('ee:_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
		<?php endif; ?>
	<?=form_close();?>
</div>

<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('addons/settings/pages'),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
