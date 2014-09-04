<?php extend_template('default') ?>

<div id="template_create">
	<?=form_open('C=design'.AMP.'M=user_message')?>
	<?=form_hidden('template_id', $template_id)?>

	<p><?=lang('user_messages_template_desc')?></p>
	<p><strong class="notice"><?=lang('user_messages_template_warning')?>:</strong> {title} {meta_refresh} {heading} {content} {link}</p>
	<p><?=form_textarea(array('id'=>'template_data','name'=>'template_data','cols'=>100,'rows'=>25,'class'=>'markItUpEditor','value'=>$template_data))?></p>
	<p><?=form_submit('template', lang('update'), 'class="submit"')?></p>
	
	<?=form_close()?>
</div>