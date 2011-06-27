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

		<div class="overview">
			<div class="heading"><h2><?=lang('available_variables')?></h2></div>
			<div class="pageContents">
				<ul>
				<?php foreach($vars as $var):?>
					<li>{<?=$var?>}</li>
				<?php endforeach;?>
				</ul>
				
				<?php if ($template == 'forum_post_notification'):?>
					<br />
					<p><?=lang('notification_has_char_limit')?></p>
				<?php endif;?>
			</div>
		</div>
		
		<div class="heading"><h2><?=lang('edit_template')?>: <?=$template_name?></h2></div>
		<div class="pageContents">
			<?php $this->load->view('_shared/message');?>						
			<div id="templateEditor" class="formArea">				
				<div id="template_create">
					<?=form_open('C=design'.AMP.'M=update_email_notification', '', array('template_id' => $template_id, 'template' => $template))?>
					
					<p>
						<?=lang('email_subject', 'template_title')?>
						<?=form_input('template_title', $template_title, 'class="fullfield"')?>
					</p>
					<p>
						<?=lang('message_body', 'template_data')?>
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
		
					<p>
						<?=form_checkbox('enable_template', 'y', (($enable_template == 'y') ? TRUE : FALSE), 'id="enable_template"')?>
						<?=lang('use_this_template', 'enable_template')?>  <?=lang('use_this_template_exp')?>
					</p>
					
					<p><?=form_submit('update', lang('update'), 'class="submit"')?> <?=form_submit('update_and_return', lang('update_and_return'), 'class="submit"')?></p>
					<?=form_close()?>

				</div>
			</div>
		</div> <!-- pageContents -->

	</div> 
	<!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_email_notification.php */
/* Location: ./themes/cp_themes/default/design/edit_email_notification.php */