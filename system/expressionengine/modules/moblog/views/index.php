<?php if (isset($moblogs) && count($moblogs) > 0):?>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog'.AMP.'method=delete_confirm')?>

	<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			lang('moblog_view'),
			lang('check_moblog'),
			lang('moblog_prefs'),
			form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"')
		);

		foreach($moblogs as $mblog)
		{
			$this->table->add_row(
									$mblog->moblog_full_name,
									($mblog->moblog_enabled == 'y') ? '<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog'.AMP.'method=check_moblog'.AMP.'moblog_id='.$mblog->moblog_id.'" class="notification_link">'.lang('check_moblog').'</a>' : lang('check_moblog'),
									'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog'.AMP.'method=create_modify'.AMP.'id='.$mblog->moblog_id.'">'.lang('moblog_prefs').'</a>',
									form_checkbox('toggle[]', $mblog->moblog_id, FALSE, 'class="toggle_moblog"')
									);
		}

	?>
			<?=$this->table->generate()?>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
	</p>

	<?=form_close()?>

<?php else:?>
	<p class="notice"><?=lang('no_moblogs')?></p>
<?php endif;?>