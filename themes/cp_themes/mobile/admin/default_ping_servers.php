<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>

		<?php if($instructions!=''):?>
			<div class="container pad"><?=$instructions?></div>
		<?php endif;?>

		<?=form_open('C=admin_content'.AMP.'M=save_ping_servers', array('id' => 'ping_server_form'), $form_hidden)?>

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
		<p class="container pad"><?=lang('pingserver_delete_instructions')?></p>

		<p><?=form_submit('ping_servers', lang('submit'), 'class="whiteButton"')?></p>

		<?=form_close()?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file default_ping_servers.php */
/* Location: ./themes/cp_themes/default/admin/default_ping_servers.php */