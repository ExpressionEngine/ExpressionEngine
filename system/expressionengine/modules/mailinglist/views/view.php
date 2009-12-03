<div id="filterMenu">

	<div class="group">

		<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view')?>
			
			
		<p>
		<?=lang('ml_email_address_field', 'email')?>
		<?=form_input(array('id'=>'email','name'=>'email','value'=>$email))?> 
					
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

		<?=lang('mailing_list', 'list')?>
		<?=form_dropdown('list_id', $mailinglists, $selected_list, 'id="list_id"')?> 
					
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

		<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
		</p>

	    <?=form_close()?>
	</div>
</div>
	



<?php if( ! empty($subscribers)):?>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=delete_confirm', '', $form_hidden)?>

	<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
//									'', // EE 1.6.X had a column for rowcount
									lang('email'),
									lang('ip'),
									lang('ml_mailinglist'), 
									form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('delete', 'select_all')
		);

		foreach($subscribers as $subscriber)
		{
			$this->table->add_row(
									
									'<a href="mailto:'.$subscriber['email'].'">'.$subscriber['email'].'</a>',
									$subscriber['ip_address'],
									$subscriber['list'],
									form_checkbox($subscriber['toggle'])
								);
		}

	?>
			<?=$this->table->generate()?>

<div class="tableFooter">
	<div class="tableSubmit">
		<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
	</div>
<span class="js_hide"><?=$pagination?></span>	
<span class="pagination" id="filter_pagination"></span>
</div>	

	<?=form_close()?>

<?php else:?>

	<p><?=lang('ml_no_results')?></p>

<?php endif;?>