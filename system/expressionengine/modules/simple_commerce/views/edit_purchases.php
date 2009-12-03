<div class="cp_button"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=add_purchase'?>"><?=lang('add_purchase')?></a></div>

<div class="cp_button"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=export_purchases'?>"><?=lang('export_purchases')?></a></div>

<div class="clear_left shun"></div>

<?php if (count($purchases) > 0): ?>
<?=form_open($action_url, '', $form_hidden)?>

<?php

		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			lang('item_purchased'),
			lang('purchaser_screen_name'),
			lang('date_purchased'),
			lang('subscription_end_date'),			
			lang('item_cost'),
			form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"'));

		foreach($purchases as $purchase)
		{
			$this->table->add_row(
					'<a href="'.$purchase['edit_link'].'">'.$purchase['entry_title'].'</a>',
					$purchase['purchaser_screen_name'],
					$purchase['date_purchased'],
					$purchase['subscription_end_date'],
					$purchase['item_cost'],
					form_checkbox($purchase['toggle'])
				);
		}


echo $this->table->generate();

$options = array(
                  'edit'  => lang('edit_selected'),
                  'delete'    => lang('delete_selected')
                );
?>

<div class="tableFooter">
	<div class="tableSubmit">
<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
	</div>
<span class="js_hide"><?=$pagination?></span>	
<span class="pagination" id="filter_pagination"></span>
</div>	

<?=form_close()?>

<?php else: ?>
<?=lang('invalid_entries')?>
<?php endif; ?>