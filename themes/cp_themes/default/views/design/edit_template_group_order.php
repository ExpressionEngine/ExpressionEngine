<?php extend_template('basic') ?>
		
<div class="heading">
	<h2><?=lang('edit_template_group_order')?></h2>
</div>

<div id="template_create" class="pageContents">

	<?php $this->load->view('_shared/message');?>

	<?=form_open('C=design'.AMP.'M=reorder_template_groups', '', $form_hidden)?>
	<?php foreach ($template_groups->result() as $template_group):?>
		<p>
			<?php 
			echo form_input(array(
								'name'=>'template_group['.$template_group->group_id.']',
								'value'=>$template_group->group_order,
								'size'=>5,
								'maxlength'=>5
								)
							);
			?> 
			<label for="<?=$template_group->group_name?>"><?=$template_group->group_name?></label>
		</p>
	<?php endforeach;?>
	<p><?=form_submit('template_group_order', lang('update'), 'class="submit"')?></p>
	<?=form_close()?>
</div>