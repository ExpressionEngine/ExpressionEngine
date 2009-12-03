<?php $this->load->view('account/_header')?>

<?=form_open('C=myaccount'.AMP.'M=save_ping_servers', array('id' => 'ping_server_form'), $form_hidden)?>

<p class="container pad"><?=lang('define_ping_servers')?></p>

<?php
foreach($ping_servers as $i => $server):?>
<ul>
	<li><?=lang('server_name')?><br /><?=form_input("server_name_$i", $server['server_name'])?></li>
	<li><?=lang('server_url')?><br /><?=form_input("server_url_$i", $server['server_url'])?></li>
	<li><?=lang('port')?><br /><?=form_input("server_port_$i", $server['port'])?></li>
	<li><?=lang('protocol')?><br /><?=form_dropdown('ping_protocol_'.$i, $protocols, $server['ping_protocol'])?></li>
	<li><?=lang('is_default')?><br /><?=form_dropdown('is_default_'.$i, $is_default_options, $server['is_default'])?></li>
</ul>
<?php endforeach;?>
<ul>
	<li><?=form_input("server_name_$blank_count", '', 'placeholder="'.lang('server_name').'"')?></li>
	<li><?=form_input("server_url_$blank_count", '', 'placeholder="'.lang('server_url').'"')?></li>
	<li><?=lang('port')?><br /><?=form_input("server_port_$blank_count", '80')?></li>
	<li><?=form_dropdown('ping_protocol_'.$blank_count, $protocols)?></li>
	<li><?=lang('is_default')?><br /><?=form_dropdown('is_default_'.$blank_count, $is_default_options)?></li>
</ul>

<p class="pad"><?=lang('pingserver_delete_instructions')?></p>

<?=form_submit('ping_server_submit', lang('submit'), 'class="whiteButton"')?>


<?=form_close()?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file index.php */
/* Location: ./themes/cp_themes/default/account/ping_servers.php */