<?php extend_template('default') ?>

<?=form_open('C=admin_content'.AMP.'M=status_group_update', '', $form_hidden)?>

	<p>
	<?=form_label(lang('new_group_name'), 'status_group_name')?>
	<?=form_input(array('id'=>'status_group_name','name'=>'group_name','class'=>'field','value'=>$group_name))?>
	</p>

	<p><?=form_submit('edit_status_group_name', lang($submit_lang_key), 'class="submit"')?></p>

<?=form_close()?>