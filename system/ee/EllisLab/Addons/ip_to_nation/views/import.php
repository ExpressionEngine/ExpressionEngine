<h3><?=lang('update_info')?></h3>
	
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation'.AMP.'method=download_data', 'id="update_form"')?>

	<p>
		<?=lang('update_blurb')?>
	</p>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('update'), 'class' => 'submit'))?>
	</p>

	<br>

	<?php if ($last_update): ?>
		<p><?=lang('last_update').$last_update?></p>
	<?php endif; ?>

	<p><?=$update_data_provider?></p>

<?=form_close()?>