<div class="clear_left">&nbsp;</div>

<?php if(count($mailinglists) == 0):?>

	<p><?=lang('ml_no_lists_exist')?></p>

<?php else:?>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=delete_mailinglist_confirm')?>

	<?php
		$this->table->set_heading(
			lang('ml_mailinglist_title'),
			lang('ml_mailinglist_name'),
			lang('ml_view_list'),
			lang('ml_edit_list'),
			lang('ml_edit_template'),
			lang('ml_total_emails'),
			form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('delete', 'select_all')
		);

		foreach($mailinglists as $list)
		{
			$this->table->add_row(
									$list['shortname'],
									$list['name'],
									'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view'.AMP.'list_id='.$list['id'].'">'.lang('ml_view').'</a>',
									'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=edit_mailing_list'.AMP.'list_id='.$list['id'].'">'.lang('edit').'</a>',
									'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=edit_template'.AMP.'list_id='.$list['id'].'">'.lang('ml_edit_template').'</a>',
									$list['count'],
									form_checkbox($list['toggle'])
									);
		}

	?>
			<?=$this->table->generate()?>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
	</p>
	<?=form_close()?>

	<div class="shun"></div>

	<?php if(count($mailinglists) > 0):?>

		<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view')?>

		<h3><?=lang('ml_email_search')?></h3>

		<p>
			<?=lang('ml_email_search_cont', 'email')?>
			<?=form_input(array('name'=>'email', 'size'=>75))?>
		</p>

		<p>
			<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
		</p>

		<?=form_close()?>

		<div class="shun"></div>

		<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=subscribe')?>

		<h3><?=lang('ml_batch_subscribe')?></h3>

		<p>
			<?=lang('ml_add_email_addresses_cont', 'addresses')?><br />
			<?=form_textarea('addresses')?>
		</p>

		<p>
			<?=form_dropdown('sub_action', array('subscribe' => lang('ml_add_email_addresses'), 'unsubscribe' => lang('ml_remove_email_addresses')))?>
		</p>

		<p>
			<?=lang('ml_select_list', 'list_id')?>
			<?=form_dropdown('list_id', $list_id_options)?>
		</p>

		<p>
			<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
		</p>

		<?=form_close()?>

	<?php endif;?>
<?php endif;?>