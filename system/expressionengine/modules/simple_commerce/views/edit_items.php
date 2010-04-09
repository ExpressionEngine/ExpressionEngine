<div class="cp_button"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=add_items'?>"><?=lang('add_items')?></a></div>
<div class="cp_button"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=export_items'?>"><?=lang('export_items')?></a></div>

<div class="clear_left shun"></div>


<?php echo validation_errors(); ?>

<?php if (count($items) > 0): ?>
<?=form_open($action_url, '', $form_hidden)?>


<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			lang('entry_title'),
			lang('regular_price'),
			lang('sale_price'),
			lang('use_sale_price'),
			lang('subscription_frequency'),
			lang('current_subscriptions'),
			lang('item_purchases'),
			form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"'));

		foreach($items as $item)
		{
			$this->table->add_row(
					'<a href="'.$item['edit_link'].'">'.$item['entry_title'].'</a>',
					$item['regular_price'],
					$item['sale_price'],
					$item['use_sale_price'],
					$item['subscription_period'],
					$item['current_subscriptions'],					
					$item['item_purchases'],
					form_checkbox($item['toggle'])
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