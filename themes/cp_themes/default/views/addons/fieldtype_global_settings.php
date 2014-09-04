<?php extend_template('default') ?>

<?=form_open('C=addons_fieldtypes'.AMP.'M=global_settings'.AMP.'ft='.$_ft_name)?>
	<?=$_ft_settings_body?>
	<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?=form_close()?>