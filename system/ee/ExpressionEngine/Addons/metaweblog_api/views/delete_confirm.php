
<?=form_open('C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=metaweblog_api' . AMP . 'method=delete')?>
<?php foreach ($damned as $mod):?>
	<?=form_hidden('delete[]', $mod)?>
<?php endforeach;?>

<h3 class="shun"><?=lang('metaweblog_delete_confirm')?></h3>

<p><?=lang('metaweblog_delete_question')?></p>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>