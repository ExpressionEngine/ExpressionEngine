<?php if ($message):?>
	<p class="notice"><?=$message?></p>
<?php endif;?>


<?php if (isset($form_hidden) AND $form_hidden['write_htaccess'] == 'y'):?>
	<?=form_open($form_action, '', $form_hidden)?>

	<p><?=lang('write_htaccess_file')?></p>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('update'), 'class' => 'submit'))?>
	</p>

	<?=form_close()?>
<?php endif;?>