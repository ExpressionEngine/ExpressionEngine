<?=form_open($action_url, '', $form_hidden)?>
<?php

foreach ($items as $key => $val):

	echo '<h4>'.$val['entry_title'].'</h4>';

	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
	    array('data' => lang('preference'), 'style' => 'width:50%;'),
	    lang('setting')
	);

	$this->table->add_row(array(
			lang('regular_price', 'regular_price['.$key.']'),
			form_error('regular_price['.$key.']').
			form_input('regular_price['.$key.']', set_value('regular_price['.$key.']', $val['regular_price']))
		)
	);
	
	$this->table->add_row(array(
			lang('sale_price', 'sale_price['.$key.']'),
			form_input('sale_price['.$key.']', set_value('sale_price['.$key.']', $val['sale_price']))
		)
	);
	
	$this->table->add_row(array(
			lang('use_sale_price', 'use_sale['.$key.']'),
			form_checkbox('use_sale['.$key.']', 'y', set_value('use_sale['.$key.']', $val['sale_price_enabled']))
		)
	);

	$this->table->add_row(array(
			lang('item_enabled', 'enabled['.$key.']'),
			form_checkbox('enabled['.$key.']', 'y', set_value('enabled['.$key.']', $val['item_enabled']))
		)
	);

	$this->table->add_row(array(
			lang('admin_email_address', 'admin_email_address['.$key.']'),
			form_error('admin_email_address['.$key.']').
			form_input('admin_email_address['.$key.']', set_value('admin_email_address['.$key.']', $val['admin_email_address']))
		)
	);

	$this->table->add_row(array(
			lang('admin_email_template', 'admin_email_template['.$key.']'),
			form_error('admin_email_template['.$key.']').
			form_dropdown('admin_email_template['.$key.']', $email_templates_dropdown, set_value('admin_email_template['.$key.']', $val['admin_email_template']))
		)
	);
	
	$this->table->add_row(array(
			lang('customer_email', 'customer_email_template['.$key.']'),
			form_dropdown('customer_email_template['.$key.']', $email_templates_dropdown, set_value('customer_email_template['.$key.']', $val['customer_email_template']))
		)
	);

	$this->table->add_row(array(
			lang('member_group', 'member_group['.$key.']'),
			form_dropdown('member_group['.$key.']', $member_groups_dropdown, set_value('member_group['.$key.']', $val['new_member_group']))
		)
	);
	
	$this->table->add_row(array(
			lang('recurring', 'recurring['.$key.']'),
			form_checkbox('recurring['.$key.']', 'y', set_value('recurring['.$key.']', $val['recurring']))
		)
	);

	$this->table->add_row(array(
			lang('subscription_frequency', 'subscription_frequency['.$key.']'),
			form_error('subscription_frequency['.$key.']').
			form_input('subscription_frequency['.$key.']', set_value('subscription_frequency['.$key.']', $val['subscription_frequency']))
		)
	);

	$this->table->add_row(array(
			lang('subscription_frequency_unit', 'subscription_frequency_unit['.$key.']'),
			form_dropdown('subscription_frequency_unit['.$key.']', $subscription_frequency_unit, set_value('subscription_frequency_unit['.$key.']', $val['subscription_frequency_unit']))
		)
	);

	$this->table->add_row(array(
			lang('admin_email_template_unsubscribe', 'admin_email_template_unsubscribe['.$key.']'),
			form_error('admin_email_template_unsubscribe['.$key.']').
			form_dropdown('admin_email_template_unsubscribe['.$key.']', $email_templates_dropdown, set_value('admin_email_template_unsubscribe['.$key.']', $val['admin_email_template_unsubscribe']))
		)
	);

	$this->table->add_row(array(
			lang('customer_email_unsubscribe', 'customer_email_template_unsubscribe['.$key.']'),
			form_dropdown('customer_email_template_unsubscribe['.$key.']', $email_templates_dropdown, set_value('customer_email_template_unsubscribe['.$key.']', $val['customer_email_template_unsubscribe']))
		)
	);

	$this->table->add_row(array(
			lang('member_group_unsubscribe', 'member_group_unsubscribe['.$key.']'),
			form_dropdown('member_group_unsubscribe['.$key.']', $member_groups_dropdown, set_value('member_group_unsubscribe['.$key.']', $val['member_group_unsubscribe']))
		)
	);

	echo $this->table->generate();
	$this->table->clear();
?>

<div class='hidden'><?=form_hidden('entry_id['.$key.']', $val['entry_id'])?></div>

<?php endforeach; ?>
	<?=form_submit(array('name' => 'submit', 'value' => lang($type), 'class' => 'submit'))?>
<?=form_close()?>