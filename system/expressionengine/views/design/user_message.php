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
				<h2><?=lang('user_message')?></h2>
		</div>
		<div class="pageContents">
			<?php $this->load->view('_shared/message');?>
			
			<div id="template_create">
				<?=form_open('C=design'.AMP.'M=user_message')?>
				<?=form_hidden('template_id', $template_id)?>

				<p><?=lang('user_messages_template_desc')?></p>
				<p><strong class="notice"><?=lang('user_messages_template_warning')?>:</strong> {title} {meta_refresh} {heading} {content} {link}</p>
				<p><?=form_textarea(array('id'=>'template_data','name'=>'template_data','cols'=>100,'rows'=>25,'class'=>'markItUpEditor','value'=>$template_data))?></p>
				<p><?=form_submit('template', lang('update'), 'class="submit"')?></p>
				
				<?=form_close()?>
			</div>
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file user_message.php */
/* Location: ./themes/cp_themes/default/design/user_message.php */