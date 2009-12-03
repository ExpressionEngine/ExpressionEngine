<?php $this->load->view('account/_account_header');?>

	<div>
		<h3><?=lang('quicklinks_manager')?></h3>

		<?=form_open('C=myaccount'.AMP.'M=quicklinks_update', '', $form_hidden)?>

		<div class="shun">
			<?=lang('quick_link_description')?> <?=lang('quick_link_description_more')?>
		</div>

		<div class="notice del_instructions"><?=lang('quicklinks_delete_instructions')?></div>

		<?php 
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
								lang('link_title'),
								lang('link_url'),
								array('data'=>lang('link_order'), 'style'=>'width: 10%;')
//								array('data'=>'', 'class'=>'del_row')
							);

		foreach($quicklinks as $i => $quicklink)
		{
			$this->table->add_row(
				form_input(array('name'=>"title_$i", 'value'=>$quicklink['title'], 'size'=>40)),
				form_input(array('name'=>"link_$i", 'value'=>$quicklink['link'], 'size'=>40)),
				form_input(array('name'=>"order_$i", 'value'=>$quicklink['order'], 'size'=>3))
				// array(
				// 	'data'=>'<img src="'.$cp_theme_url.'images/drag.png" />'.
				// 		"<input type='hidden' name='ajax_server_order[]' value='".$server['server_id']."' class='tag_order' />".
				// 		form_input(array('id'=>"server_order_{$i}",'name'=>"server_order_{$i}",'value'=>$server['server_order'], 'size'=>5)),
				// 	'class'=>'tag_order'
				// )
				// array(
				// 	'data'=>'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=delete_ping_server'.AMP.'id='.$server['server_id'].'"><img src="'.$cp_theme_url.'images/content_custom_tab_delete.png" alt="'.lang('delete').'" width="19" height="18" /></a>', 
				// 	'class'=>'del_row'
				// )
			);
		}

		$this->table->add_row(
			form_input(array('name'=>"title_$blank_count", 'value'=>'', 'size'=>40)),
			form_input(array('name'=>"link_$blank_count", 'value'=>'http://', 'size'=>40)),
			form_input(array('name'=>"order_$blank_count", 'value'=>$blank_count, 'size'=>3))
			// array(
			// 	'data'=>'<img src="'.$cp_theme_url.'images/drag.png" />'.
			// 		"<input type='hidden' name='ajax_server_order[]' value='".$blank_count."' class='tag_order' />".
			// 		form_input(array('id'=>"server_order_{$blank_count}",'name'=>"server_order_{$blank_count}",'value'=>$blank_count)),
			// 	'class'=>'tag_order'
			// )
			// array(
			// 	'data'=>'', 
			// 	'class'=>'del_row'
			// )
		);

		echo $this->table->generate();

		?>

		<p class="submit"><?=form_submit('quicklinks_update', lang('submit'), 'class="submit"')?></p>

		<?=form_close()?>
	</div>

<?php $this->load->view('account/_account_footer');