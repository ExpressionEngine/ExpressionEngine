<div class="cp_button"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=add_purchase'?>"><?=lang('add_purchase')?></a></div>

<div class="cp_button"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=export_purchases'?>"><?=lang('export_purchases')?></a></div>

<div class="clear_left shun"></div>

<?=form_open($action_url, '', $form_hidden)?>

<?php
echo $table_html;

$options = array(
	'edit'	=> lang('edit_selected'),
	'delete'=> lang('delete_selected')
);
?>

<div class="tableFooter">
	<div class="tableSubmit">
		<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
	</div>
	<?=$pagination_html?>
</div>	

<?=form_close()?>