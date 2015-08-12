<div class="box">
	<div class="tbl-ctrls">
		<?=form_open($base_url)?>
			<fieldset class="tbl-search right">
			</fieldset>
			<h1><?=lang('moderators')?><br><i><?=lang('moderators_desc')?></i></h1>
			<?=ee('Alert')->getAllInlines()?>

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
			<?php endif; ?>

		<?=form_close();?>
	</div>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-moderators',
	'form_url'	=> ee('CP/URL', $this->base . 'remove/moderator'),
	'hidden'	=> array(
		'id' => ''
	)
);

$this->embed('ee:_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>
