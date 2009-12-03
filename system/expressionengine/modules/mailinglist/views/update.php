<?=form_open($form_action, '', $form_hidden)?>

	<p>
		<?=lang('ml_mailinglist_short_name', 'list_name')?><br />
		<?=lang('ml_mailinglist_short_info')?> 
		<?=form_input(array('id'=>'list_name','name'=>'list_name','class'=>'field', 'value'=> set_value('list_name', $list_name)))?>
		<?=form_error('list_name')?>
	</p>

	<p>
		<?=lang('ml_mailinglist_long_name', 'list_title')?><br />
		<?=lang('ml_mailinglist_long_info')?> 
		<?=form_input(array('id'=>'list_title','name'=>'list_title','class'=>'field', 'value'=> set_value('list_title', $list_title)))?>
		<?=form_error('list_title')?>
	</p>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => $button_label, 'class' => 'submit'))?>
	</p>

<?=form_close()?>
