<?php extend_template('default') ?>

<?=form_open('C=admin_content'.AMP.'M=field_group_update', '', $form_hidden)?>

	<p>
		<?=form_label(lang('new_group_name'), 'group_name')?>
		<?=form_input(array('id'=>'group_name','name'=>'group_name','class'=>'field','value'=>$group_name))?>
	</p>

	<p><?=form_submit('edit_field_group_name', lang($submit_lang_key), 'class="submit"')?></p>

<?=form_close()?>