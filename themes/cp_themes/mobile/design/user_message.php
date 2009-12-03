<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	
	<div class="container pad">
		<?=form_open('C=design'.AMP.'M=user_message')?>
		<?=form_hidden('template_id', $template_id)?>

		<p><?=lang('user_messages_template_desc')?></p>
		<p><strong class="notice"><?=lang('user_messages_template_warning')?>:</strong> {title} {meta_refresh} {heading} {content} {link}</p>
		<p><?=form_textarea(array('id'=>'template_data','name'=>'template_data','cols'=>40,'rows'=>25,'class'=>'markItUpEditor','value'=>$template_data))?></p>
		<p><?=form_submit('template', lang('update'), 'class="whiteButton"')?></p>
		
		<?=form_close()?>
	</div>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file user_message.php */
/* Location: ./themes/cp_themes/mobile/design/user_message.php */