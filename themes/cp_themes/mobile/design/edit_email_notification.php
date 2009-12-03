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
	
	<!-- <div class="container pad"> -->

		<?=form_open('C=design'.AMP.'M=update_email_notification', '', array('template_id' => $template_id, 'template' => $template))?>
		<p>

		<?=form_textarea(array(
								'name'	=> 'template_data',
				              	'id'	=> 'template_data',
				              	'cols'	=> '100',
				              	'rows'	=> '20',
								'value'	=> $template_data,
								'style'	=> 'border: 0;',
								'class'	=> 'markItUpEditor'
						));?>
		</p>


		<p><?=form_submit('update', lang('update'), 'class="whiteButton"')?> <?=form_submit('update_and_return', lang('update_and_return'), 'class="whiteButton"')?></p>
		<?=form_close()?>

</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file email_notification.php */
/* Location: ./themes/cp_themes/mobile/design/email_notification.php */