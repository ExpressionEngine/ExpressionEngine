<?php extend_template('default') ?>

<?=form_open('C=design'.AMP.'M=template_delete'.AMP.'tgpref='.$group_id, '', $form_hidden)?>

	<p><strong><?=lang('delete_this_template')?></strong></p>

	<p><?=$template_name?></p>
	<?php if ($file !== FALSE): ?>
	<p><strong><?=lang('file_exists_warning')?></strong></p>
	<?php endif; ?>

	<p class="notice"><?=lang('action_can_not_be_undone')?></p>

	<p><?=form_submit('delete_template', lang('delete'), 'class="submit"')?></p>

<?=form_close()?>