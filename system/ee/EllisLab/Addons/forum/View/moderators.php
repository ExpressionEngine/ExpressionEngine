<div class="box table-list-wrap">
	<div class="tbl-ctrls">
		<?=form_open($base_url)?>
			<fieldset class="tbl-search right">
			</fieldset>
			<h1><?=lang('moderators')?><br><i><?=lang('moderators_desc')?></i></h1>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

			<?php if (empty($categories)): ?>
			<table cellspacing="0" class="empty no-results">
				<tr>
					<td><?=sprintf(lang('no_found'), lang('categories'))?> <a class="btn action" href="<?=ee('CP/URL')->make('addons/settings/forum/create/category/' . $board->board_id)?>"><?=lang('create_new_category')?></a></td>
				</tr>
			</table>
			<?php else: ?>
				<?php
				foreach ($categories as $category)
				{
					$this->embed('ee:_shared/table', $category);
				}
				?>
			<?php endif; ?>

		<?=form_close();?>
	</div>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-moderators',
	'form_url'	=> $remove_url,
	'hidden'	=> array(
		'id' => ''
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('moderators', $modal);
?>
