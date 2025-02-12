<h3><?=lang('mailing_list')?> <?=$list_title?></h3>

<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=update_template', '', $form_hidden)?>

	<p><span class="notice"><?=lang('mailinglist_template_warning')?></span> {message_text}, {unsubscribe_url}</p>

	<p>
		<?=form_textarea(array('id'=>'template_data','name'=>'template_data','class'=>'field', 'value'=>$template_data))?>
	</p>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('update'), 'class' => 'submit'))?>
	</p>

<?=form_close()?>
