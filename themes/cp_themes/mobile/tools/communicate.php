<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>

<div id="communicate" class="current">
	<div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=tools"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	
	
	
	<?php $this->load->view('_shared/right_nav')?>
		<div class="contents">
			<?php if ($view_email_cache): ?>
				<div class="container pad"><p><?=lang('view_email_cache')?></p></div>
			<?php else: ?>
				<div class="container pad"><p><?=lang('send_an_email')?> <span class="headingSubtext">(<a href="<?=BASE.AMP.'C=tools_communicate'.AMP.'M=view_cache'?>">View Previously Sent Email</a>)</span></p></div>
			<?php endif; ?>
			<?php if ($alert): ?>
			<p class="notice"><?=$alert?></p>
			<?php endif; ?>

			<?=form_open_multipart('C=tools_communicate'.AMP.'M=send_email')?>
			<ul>
				<li><?=form_input(array(
									'id'			=> 'name',
									'name'			=> 'name',
									'class'			=> 'fullfield',
									'value' 		=> set_value('name', $name),
									'placeholder'  	=> lang('your_name')))?></li>
				<?php if (form_error('from')):?>
				<li><?=form_error('from')?></li>
				<?php endif; ?>
				<li><?=form_input(array(
									'id'			=> 'from',
									'name'			=> 'from',
									'class'			=> 'fullfield',
									'value'			=> set_value('from', $from),
									'placeholder'	=> '* '.lang('your_email')))?></li>
				<?php if (form_error('recipient')):?>
					<?=form_error('recipient')?>
				<?php endif;?>
				<li><?=form_input(array(
									'id'			=> 'recipient',
									'name'			=> 'recipient',
									'class'			=> 'fullfield',
									'value'			=> set_value('recipient', $recipient),
									'placeholder'	=> lang('recipient')))?>
				</li>
				<?php if (form_error('cc')):?>
					<li><?=form_error('cc')?></li>
				<?php endif;?>
				<li><?=form_input(array(
									'id'			=> 'cc',
									'name'			=> 'cc',
									'class'			=> 'fullfield',
									'value'			=> set_value('cc', $cc),
									'placeholder'	=> lang('cc')))?>
				</li>
				<?php if(form_error('bcc')):?>
				<li>
					<?=form_error('bcc')?>
				</li>
				<?php endif;?>
				<li><?=form_input(array(
									'id'			=> 'bcc',
									'name'			=> 'bcc',
									'class'			=> 'fullfield',
									'value'			=> set_value('bcc', $bcc),
									'placeholder'	=> lang('bcc')))?>
				</li>
			</ul>
			<?php if (is_array($mailing_lists)):?>
			<h3><?=lang('send_to_mailinglist')?></h3>
			<ul>
				<?php foreach ($mailing_lists as $list => $details): ?>
				<li><label><?=form_checkbox($details)?> &nbsp;<?=$list?></label></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
			<?php if (is_array($member_groups)):?>
			<h3><?=lang('recipient_group')?></h3>
			<ul>
				<?php foreach ($member_groups as $group => $details): ?>
				<li><label><?=form_checkbox($details)?> &nbsp;<?=$group?></label></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>	
		
			<ul>
				<?php if (form_error('subject')):?>
				<li><?=form_error('subject')?></li>
				<?php endif?>
				<li><?=form_input(array(
									'id'			=> 'subject',
									'name'			=> 'subject',
									'class'			=> 'fullfield',
									'value'			=> set_value('subject', $subject),
									'placeholder'	=> lang('subject')))?>
				</li>
				<?php if (form_error('message')):?>
					<li><?=form_error('message')?></li>
				<?php endif;?>
				<li><?php
					$message = ($message) ? $message : lang('message');
					echo form_textarea(array(
									'id'			=> 'message',
									'name'			=> 'message',
									'rows'			=> 20,
									'cols'			=> 85,
									'class'			=> 'fullfield',
									'value'			=> set_value('message', $message)))?>
				</li>
			
			</ul>

			<ul>
				<li>
					<?=lang('text_formatting', 'text_fmt')?>
					<?=form_dropdown('text_fmt', $text_formatting_options, $text_fmt, 'id="text_fmt"')?>
				</li>
				<li>
					<?=lang('wordwrap', 'wordwrap')?>
					<?=form_dropdown('wordwrap', $word_wrap_options, $wordwrap, 'id="wordwrap"')?>
				</li>
				<li>
					<?=lang('priority', 'priority')?>
					<?=form_dropdown('priority', $priority_options,  $priority, 'id="priority"')?>
				</li>
			</ul>
				<div class="container pad">
					<p style="margin-top:15px;">
						<?php
						$accept_admin_email = array(
						  'name'        => 'accept_admin_email',
						  'id'          => 'accept_admin_email',
						  'value'       =>  'y',
						  'checked'		=> set_checkbox('accept_admin_email', 'y')
						);
						echo form_checkbox($accept_admin_email);?> 
						
						<?=lang('honor_email_pref', 'accept_admin_email')?>
					</p>

					<p><strong class="notice">*</strong> <?=lang('required_fields')?></p>
				
<?=form_submit(array('name' => 'submit', 'value' => lang('send_it'), 'class="whiteButton"'))?>
			<?=form_close()?>
</div>
	</div>
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file communicate.php */
/* Location: ./themes/cp_themes/default/tools/communicate.php */