<?=form_open($form_action)?>
<?php foreach($damned as $list):?>
	<?=form_hidden('delete[]', $list)?>
<?php endforeach;?>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<h3><?=lang('moblog_delete_question')?></h3>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>