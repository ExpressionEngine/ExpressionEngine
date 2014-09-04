<?php extend_template('default') ?>
	
<div id="template_create">

	<?=form_open('C=design'.AMP.'M=system_offline')?>
	<?=form_hidden('template_id', $template_id)?>

	<?php $this->load->view('_shared/message');?>

	<p><?=lang('offline_template_desc')?></p>

	<p><?=form_textarea(array('id'=>'template_data','name'=>'template_data','cols'=>100,'rows'=>25,'class'=>'markItUpEditor','value'=>$template_data))?></p>
	<p><?=form_submit('template', lang('update'), 'class="submit"')?></p>
	<?=form_close()?>

</div>