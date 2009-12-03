
	<?=form_open($form_action, '', $form_hidden)?>

		<h3><?=lang('ref_ip', 'ip')?></h3>
		<p>
			<?=form_textarea($list_item['ip'])?>
		</p>
	
		<h3><?=lang('ref_user_agent', 'agent')?></h3>
		<p>
			<?=form_textarea($list_item['agent'])?>
		</p>

		<h3><?=lang('ref_url', 'url')?></h3>
		<p>
			<?=form_textarea($list_item['url'])?>
		</p>

		<?php if($write_to_htaccess):?>
		<p>
			<?=form_checkbox('write_htaccess', 'y', TRUE, 'id="write_htaccess"')?> 
			<?=lang('write_htaccess_file', 'write_htaccess')?>
		</p>
		<?php endif;?>

		<p>
			<?=form_submit(array('name' => 'submit', 'value' => lang('update'), 'class' => 'submit'))?>
		</p>

	<?=form_close()?>