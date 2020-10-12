<div class="panel">
	<div class="tbl-ctrls">
		<?=form_open(ee('CP/URL')->make('addons/settings/forum'))?>
			<?php if (empty($board)): ?>
      <div class="table-responsive table-responsive--collapsible">
        <table cellspacing="0" class="empty">
					<tr class="no-results">
						<td><?=sprintf(lang('no_found'), lang('forum_boards'))?> <a href="<?=ee('CP/URL')->make('addons/settings/forum/create/board')?>"><?=lang('create_new_board')?></a></td>
					</tr>
				</table>
      </div>
			<?php else: ?>
      <div class="panel-heading">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
        <div class="form-btns form-btns-top">
          <div class="title-bar title-bar--large">
      			<h3 class="title-bar__title"><?=$board->board_label?> <?=lang('forum_listing')?>
      				<i style="text-align: left;"><?=$board->board_name?>,
      				<?php if ($board->board_enabled): ?>
      				<span class="yes"><?=lang('enabled')?></span>
      				<?php else: ?>
      				<span class="no"><?=lang('disabled')?></span>
      				<?php endif; ?>
      				</i>
      			</h3>
            <div class="title-bar__extra-tools">
              <a class="button button--primary" href="<?=ee('CP/URL')->make('addons/settings/forum/create/category/' . $board->board_id)?>"><?=lang('new_category')?></a>
            </div>
          </div>
        </div>

  			<fieldset class="tbl-filter">
  				<ul class="toolbar">
  					<li class="mods"><a href="<?=ee('CP/URL')->make('addons/settings/forum/moderators/' . $board->board_id)?>" title="<?=lang('moderators')?>"></a></li>
  					<li class="admin"><a href="<?=ee('CP/URL')->make('addons/settings/forum/admins/' . $board->board_id)?>" title="<?=lang('administrators')?>"></a></li>
  				</ul>
  			</fieldset>
      </div>

			<?php if (empty($categories)): ?>
      <div class="table-responsive table-responsive--collapsible">
      <table cellspacing="0" class="empty">
				<tr class="no-results">
					<td><?=sprintf(lang('no_found'), lang('categories'))?> <a href="<?=ee('CP/URL')->make('addons/settings/forum/create/category/' . $board->board_id)?>"><?=lang('create_new_category')?></a></td>
				</tr>
			</table>
			<?php else: ?>
				<?php $total = count($categories);
					foreach ($categories as $key => $category): ?>
					<div class="tbl-wrap <?=($key + 1 != $total) ? 'mb' : '' ?>">
						<?=$this->embed('ee:_shared/table', $category);?>
					</div>
				<?php endforeach; ?>

				<?php $this->embed('ee:_shared/form/bulk-action-bar', [
					'options' => [
						[
							'value' => "",
							'text' => '-- ' . lang('with_selected') . ' --'
						],
						[
							'value' => "remove",
							'text' => lang('delete'),
							'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-remove-forum"'
						]
					],
					'modal' => true
				]); ?>
      </div>
			<?php endif; ?>
		<?php endif; ?>

		<?=form_close();?>
	</div>
</div>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove-forum',
	'form_url'	=> ee('CP/URL')->make('addons/settings/forum'),
	'hidden'	=> array(
		'return' => ee('CP/URL')->getCurrentUrl()->encode(),
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove-forum', $modal);
?>
