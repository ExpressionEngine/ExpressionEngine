<?php extend_template('default') ?>

<?=form_open('C=content_files'.AMP.'M=delete_files')?>
	<?php foreach($files as $file):?>
		<?=form_hidden('file[]', $file)?>
	<?php endforeach;?>

	<p class="notice"><?=lang($del_notice)?></p>

	<p class="notice"><?=lang('action_can_not_be_undone')?></p>

	<p><?=form_submit('delete_file', lang('delete'), 'class="submit"')?></p>

<?=form_close()?>