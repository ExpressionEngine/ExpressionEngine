<div class="cp_button"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce'.AMP.'method=add_email'?>"><?=lang('add_email')?></a></div>

<div class="clear_left shun"></div>


<?php echo validation_errors(); ?>

<?php if (count($email_templates) > 0): ?>
<?=form_open($action_url, '', $form_hidden)?>

<?php

		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			lang('template_name'),
			form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"'));

		foreach($email_templates as $template)
		{
			$this->table->add_row(
					'<a href="'.$template['edit_link'].'">'.$template['email_name'].'</a>',
					form_checkbox($template['toggle'])
				);
		}

echo $this->table->generate();
$options = array(
                  'edit'  => lang('edit_selected'),
                  'delete'    => lang('delete_selected')
                );
?>

<div class="tableFooter">
	<div class="tableSubmit">
<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit')).NBS.NBS.form_dropdown('action', $options)?>
	</div>
<span class="js_hide"><?=$pagination?></span>	
<span class="pagination" id="filter_pagination"></span>
</div>	

<?=form_close()?>

<?php else: ?>
<?=lang('invalid_entries')?>
<?php endif; ?>