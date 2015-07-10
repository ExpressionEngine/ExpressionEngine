<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages'.AMP.'method=delete', '', $form_hidden)?>
<?php foreach($damned as $page):?>
	<?=form_hidden('delete[]', $page)?>
<?php endforeach;?>

<p class="shun"><?=lang('pages_delete_question')?></p>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>