<?php
	extend_template('default');

	$this->table->set_heading(array('data' => lang('accessory_name'), 'width' => '50%'), lang('available_to_member_groups'), lang('specific_page'), lang('status'));
	
	foreach ($accessories as $accessory)
	{
		$title = ($accessory['acc_pref_url']) ? "<a href='{$accessory['acc_pref_url']}'>{$accessory['name']}</a>" : $accessory['name'];

		$this->table->add_row(
			"<strong>{$title}</strong> ({$accessory['version']})<br />{$accessory['description']}",
			(count($accessory['acc_member_groups']) > 0) ? ul($accessory['acc_member_groups'], array('style'=>'list-style:disc!important; margin-left: 15px;')) : '',
			(count($accessory['acc_controller']) > 0) ? ul($accessory['acc_controller'], array('style'=>'list-style:disc!important; margin-left: 15px;')) : '',
			$accessory['acc_install']
		);

	}
	
	echo $this->table->generate();
?>