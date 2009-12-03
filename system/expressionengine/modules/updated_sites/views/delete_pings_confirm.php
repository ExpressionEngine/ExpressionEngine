
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites'.AMP.'method=delete_pings', '', $form_hidden)?>
<?php foreach($damned as $mod):?>
	<?=form_hidden('delete[]', $mod)?>
<?php endforeach;?>

<p><?=lang('ping_delete_question')?></p>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>