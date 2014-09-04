<?php extend_template('default') ?>

<?=form_open('C=content_edit'.AMP.'M=delete_entries')?>

	<?php foreach($damned as $entry_id):?>
		<?=form_hidden('delete[]', $entry_id)?>
	<?php endforeach;?>

	<p><strong><?=$message?></strong></p>

	<?php if ($title_deleted_entry != ''):?>
		<p><?=$title_deleted_entry?></p>
	<?php endif;?>

	<p class="notice"><?=lang('action_can_not_be_undone')?></p>

	<p><?=form_submit('delete_members', lang('delete'), 'class="submit"')?></p>

<?=form_close()?>