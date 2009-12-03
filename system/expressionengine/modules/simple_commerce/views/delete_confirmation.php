<?=form_open($action_url)?>
<?php foreach($damned as $name => $list):?>
	<?=form_hidden($name, $list)?>
<?php endforeach;?>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>

<?php
/* End of file delete_confirm.php */
/* Location: ./system/expressionengine/modules/wiki/views/delete_confirm.php */