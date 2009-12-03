<?php $this->load->view('account/_account_header');?>

	<div>
		<h3><?=lang('ping_servers')?></h3>

		<?=form_open('C=myaccount'.AMP.'M=save_ping_servers', array('id' => 'ping_server_form'), $form_hidden)?>

		<p><?=lang('define_ping_servers')?></p>

		<?php 
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
								lang('server_name'),
								lang('server_url'),
								lang('port'),
								lang('protocol'),
								lang('is_default'),
								lang('server_order'),
								array('data'=>'', 'class'=>'del_row')
							);

		foreach($ping_servers as $i => $server)
		{
			$this->table->add_row(
				form_input(array('name'=>"server_name_$i", 'value'=>$server['server_name'])),
				form_input(array('name'=>"server_url_$i", 'value'=>$server['server_url'])),
				form_input(array('name'=>"server_port_$i", 'value'=>$server['port'], 'style'=>'width:25px')),
				form_dropdown('ping_protocol_'.$i, $protocols, $server['ping_protocol']),
				form_dropdown('is_default_'.$i, $is_default_options, $server['is_default']),
				array(
					'data'=>'<img src="'.$cp_theme_url.'images/drag.png" class="order_arrows" style="display: none;" />'.
						form_input(array('id'=>"server_order_{$i}", 'name'=>"server_order_{$i}", 'value'=>$server['server_order'], 'style'=>'width:25px')),
					'class'=>'tag_order'
				),
				array(
					'data'=>'<a href="#"><img src="'.$cp_theme_url.'images/content_custom_tab_delete.png" alt="'.lang('delete').'" width="19" height="18" /></a>', 
					'class'=>'del_row'
				)
			);
		}

		$this->table->add_row(
			form_input(array('name'=>"server_name_$blank_count", 'value'=>'')),
			form_input(array('name'=>"server_url_$blank_count", 'value'=>'')),
			form_input(array('name'=>"server_port_$blank_count", 'value'=>'80', 'style'=>'width:25px')),
			form_dropdown('ping_protocol_'.$blank_count, $protocols),
			form_dropdown('is_default_'.$blank_count, $is_default_options),
			array(
				'data'=>'<img src="'.$cp_theme_url.'images/drag.png" class="order_arrows" style="display: none;" />'.
					form_input(array('id'=>"server_order_{$blank_count}",'name'=>"server_order_{$blank_count}",'value'=>$blank_count, 'style'=>'width:25px')),
				'class'=>'tag_order'
			),
			array(
				'data'=>'', 
				'class'=>'del_row'
			)
		);

		echo $this->table->generate();

		?>

		<p class="notice del_instructions"><?=lang('pingserver_delete_instructions')?></p>

		<p class="submit"><?=form_submit('ping_server_submit', lang('submit'), 'class="submit"')?></p>


		<?=form_close()?>
	</div>

<?php $this->load->view('account/_account_footer');