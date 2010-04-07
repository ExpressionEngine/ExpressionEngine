<?php

echo form_open($action_url, '', $form_hidden);

foreach ($purchases as $key => $val):

	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
	    array('data' => lang('preference'), 'style' => 'width:50%;'),
	    lang('setting')
	);

	$this->table->add_row(array(
			lang('txn_id', 'txn_id['.$key.']'),
			form_error('txn_id['.$key.']').
			form_input('txn_id['.$key.']', set_value('txn_id['.$key.']', $val['txn_id']))
		)
	);

	$this->table->add_row(array(
			lang('screen_name', 'screen_name['.$key.']'),
			form_error('screen_name['.$key.']').
			form_input('screen_name['.$key.']', set_value('screen_name['.$key.']', $val['screen_name']))
		)
	);

	$this->table->add_row(array(
			lang('item_purchased', 'item_id['.$key.']'),
			form_error('item_id['.$key.']').
			form_dropdown('item_id['.$key.']', $items_dropdown, set_value('item_id['.$key.']', $val['item_id']))
		)
	);

	$this->table->add_row(array(
			lang('item_cost', 'item_cost['.$key.']'),
			form_error('item_cost['.$key.']').
			form_input('item_cost['.$key.']', set_value('item_cost['.$key.']', $val['item_cost']))
		)
	);

	$this->table->add_row(array(
			lang('purchase_date', 'purchase_date['.$key.']'),
			form_error('purchase_date['.$key.']').
			form_input('purchase_date['.$key.']', set_value('purchase_date['.$key.']', $val['purchase_date']), 'id="purchase_date_'.$key.'"')
		)
	);

	$this->table->add_row(array(
			lang('subscription_end_date', 'subscription_end_date['.$key.']'),
			form_error('subscription_end_date['.$key.']').
			form_input('subscription_end_date['.$key.']', set_value('subscription_end_date['.$key.']', $val['subscription_end_date']), 'id="subscription_end_date_'.$key.'"')
		)
	);

	echo $this->table->generate();
	$this->table->clear();
?>


<div class='hidden'><?=form_hidden('purchase_id['.$key.']', $val['purchase_id'])?></div>

<?php endforeach; ?>

<?=form_submit(array('name' => 'submit', 'value' => $type, 'class' => 'submit'))?>

<?=form_close()?>