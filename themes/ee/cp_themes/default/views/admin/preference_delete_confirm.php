<?php extend_template('default') ?>

<?=form_open($form_action, $form_extra, $form_hidden)?>
	<p class="notice"><?=$message?></p>

	<p>
	<?php foreach ($items as $item): ?>
	<?=$item?><br />
	<?php endforeach; ?>
	</p>

	<p><?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?></p>
<?=form_close()?>