<?php extend_template('default') ?>

<?=form_open('C=addons_plugins'.AMP.'M=remove')?>
<?php
	foreach($hidden as $plugin)
	{
		echo form_hidden('deleted[]', $plugin);
	}
?>

<p class="go_notice"><?=lang($message)?></p>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<p><?=form_submit('delete', lang('plugin_remove'), 'class="submit"')?></p>

<?=form_close()?>