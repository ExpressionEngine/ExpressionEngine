<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

		<div class="heading">
				<h2><?=$cp_page_title?></h2>
		</div>

		<div class="pageContents">

			<?php $this->load->view('_shared/message');?>

			<?php if($instructions!=''):?>
				<p><?=$instructions?></p>
			<?php endif;?>

			<?=form_open('C=admin_content'.AMP.'M=save_ping_servers', array('id' => 'ping_server_form'), $form_hidden)?>

			<?php 
			$this->table->set_template($cp_table_template);
			$this->table->set_heading(
									lang('server_name'),
									lang('server_url'),
									array('data' => lang('port'), 'style' => 'width:30px'),
									lang('protocol'),
									lang('is_default'),
									lang('server_order'),
									array('data'=>'', 'class'=>'del_row')
								);

			foreach($ping_servers as $i => $server)
			{
				$this->table->add_row(
					form_input(array('name' => "server_name_{$i}", 'value' => $server['server_name'], 'class' => 'field')),
					form_input(array('name' => "server_url_{$i}", 'value' => $server['server_url'], 'class' => 'field     ')),
					form_input(array('name' => "server_port_{$i}", 'value' => $server['port'], 'style' => 'width:30px')),
					form_dropdown('ping_protocol_'.$i, $protocols, $server['ping_protocol']),
					form_dropdown('is_default_'.$i, $is_default_options, $server['is_default']),
					array(
						'data'=>'<img src="'.$cp_theme_url.'images/drag.png" />'.
							form_input(array('id'=>"server_order_{$i}",'name'=>"server_order_{$i}",'value'=>$server['server_order'], 'size'=>5)),
						'class'=>'tag_order'
					),
					array(
						'data'=>'<a href="#"><img src="'.$cp_theme_url.'images/content_custom_tab_delete.png" alt="'.lang('delete').'" width="19" height="18" /></a>', 
						'class'=>'del_row'
					)
				);
			}

			$this->table->add_row(
				form_input(array('name' => "server_name_{$blank_count}", 'value' => '', 'class' => 'field')),
				form_input(array('name' => "server_url_{$blank_count}", 'value' => '', 'class' => 'field')),
				form_input(array('name' => "server_port_{$blank_count}", 'value'=>'80', 'style' => 'width:30px')),
				form_dropdown('ping_protocol_'.$blank_count, $protocols),
				form_dropdown('is_default_'.$blank_count, $is_default_options),
				array(
					'data'=>'<img src="'.$cp_theme_url.'images/drag.png" />'.
						form_input(array('id'=>"server_order_{$blank_count}",'name'=>"server_order_{$blank_count}",'value'=>$blank_count)),
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

			<p><?=form_submit('ping_servers', lang('submit'), 'class="submit"')?></p>

			<?=form_close()?>
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file default_ping_servers.php */
/* Location: ./themes/cp_themes/default/admin/default_ping_servers.php */