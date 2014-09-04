<?php extend_template('default') ?>
		
<?=form_open('C=sites'.AMP.'M=delete_site', '', array('site_id' => $site_id))?>

	<p><strong><?=$message?></strong></p>
	<p>	<?=$site_label?><br /></p>
	<p class="notice"><?=lang('action_can_not_be_undone')?></p>

	<p><?=form_submit('delete_site', lang('delete_site'), 'class="submit"')?></p>

<?=form_close()?>