<div class="box">
	<div class="tbl-ctrls">
		<?=form_open(ee('CP/URL', 'addons/settings/forum'))?>
			<?php if (empty($board)): ?>
				<table cellspacing="0" class="empty no-results">
					<tr>
						<td><?=lang('no_forum_boards')?> <a class="btn action" href="<?=ee('CP/URL', 'addons/settings/forum/create/board')?>"><?=lang('create_new_board')?></a></td>
					</tr>
				</table>
			<?php else: ?>
			<fieldset class="tbl-search right">
				<a class="btn tn action" href="<?=ee('CP/URL', 'addons/settings/forum/create/category/' . $board->board_id)?>"><?=lang('new_category')?></a>
			</fieldset>
			<h1><?=$board->board_label?> <?=lang('forum_listing')?><br>
				<i><?=$board->board_name?>,
				<?php if ($board->board_enabled): ?>
				<span class="yes"><?=lang('enabled')?></span>
				<?php else: ?>
				<span class="no"><?=lang('disabled')?></span>
				<?php endif; ?>
				</i>
			</h1>
			<?=ee('Alert')->getAllInlines()?>
			<fieldset class="tbl-filter">
				<ul class="toolbar">
					<li class="mods"><a href="<?=ee('CP/URL', 'addons/settings/forum/moderators/' . $board->board_id)?>" title="<?=lang('moderators')?>"></a></li>
					<li class="admin"><a href="<?=ee('CP/URL', 'addons/settings/forum/admins/' . $board->board_id)?>" title="<?=lang('administrators')?>"></a></li>
				</ul>
			</fieldset>

			<?php if (empty($categories)): ?>
			<table cellspacing="0" class="empty no-results">
				<tr>
					<td><?=lang('no_categories')?> <a class="btn action" href="<?=ee('CP/URL', 'addons/settings/forum/create/category/' . $board->board_id)?>"><?=lang('create_new_category')?></a></td>
				</tr>
			</table>
			<?php else: ?>
				<?php
				foreach ($categories as $category)
				{
					$this->embed('ee:_shared/table', $category);
				}
				?>

				<fieldset class="tbl-bulk-act hidden">
					<select name="bulk_action">
						<option value="">-- <?=lang('with_selected')?> --</option>
						<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-forum"><?=lang('remove')?></option>
					</select>
					<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
				</fieldset>
			<?php endif; ?>
		<?php endif; ?>

		<?=form_close();?>
	</div>
</div>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove-forum',
	'form_url'	=> ee('CP/URL', 'addons/settings/forum'),
	'hidden'	=> array(
		'return' => base64_encode(ee()->cp->get_safe_refresh()),
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove-forum', $modal);
?>
