<?php extend_template('default') ?>

<?=form_open_multipart('C=skeleton'.AMP.'M=import', array('id'=>'skeleton_import'))?>

	<input type="file" name="userfile" size="20" />
	<p>
		<?=form_submit(array('name' => 'import_skeleton', 'value' => lang('submit'), 'class' => 'submit'))?>
	</p>
<?=form_close()?>
