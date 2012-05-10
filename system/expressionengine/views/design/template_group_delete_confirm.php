<?php extend_template('default') ?>

<?=form_open('C=design'.AMP.'M=template_group_delete', '', $form_hidden)?>

	<p><strong><?=lang('delete_this_group')?></strong></p>

	<p><?=$template_group_name?></p>
	<?php if ($file_folder == TRUE): ?>
	<p><strong><?=lang('folder_exists_warning')?></strong></p>
	<?php endif; ?>

	<p class="notice"><?=lang('action_can_not_be_undone')?></p>

	<p><?=form_submit('delete_template_group', lang('delete'), 'class="submit"')?></p>

<?=form_close()?>