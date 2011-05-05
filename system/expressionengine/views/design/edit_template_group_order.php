<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">
		
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

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_template_group_order.php */
/* Location: ./themes/cp_themes/default/design/edit_template_group_order.php */