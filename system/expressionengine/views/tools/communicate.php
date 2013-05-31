<?php extend_template('basic') ?>

<?php if ($view_email_cache): ?>
	<div class="heading"><h2 class="edit"><?=lang('view_email_cache')?></h2></div>
<?php else: ?>
	<div class="heading"><h2 class="edit"><?=lang('send_an_email')?> <span class="headingSubtext">(<a href="<?=BASE.AMP.'C=tools_communicate'.AMP.'M=view_cache'?>"><?= lang('view_email_cache')?></a>)</span></h2></div>
<?php endif; ?>
	<div class="pageContents">

		<?=form_open_multipart('C=tools_communicate'.AMP.'M=send_email')?>

		<div id="communicate_info">

			<p>
				<?=lang('your_name', 'name')?> 
				<?=form_input(array('id'=>'name','name'=>'name','class'=>'fullfield','value'=>set_value('name', $name)))?>
			</p>

			<p>
				<strong class="notice">*</strong> <?=lang('your_email', 'your_email')?> 
				<?=form_input(array('id'=>'from','name'=>'from','class'=>'fullfield','value'=>set_value('from', $from)))?>
				<?=form_error('from')?>
			</p>

			<p>
				<?=lang('recipient', 'recipient')?> <br />
				<?=lang('separate_emails_with_comma')?>
				<?=form_input(array('id'=>'recipient','name'=>'recipient','class'=>'fullfield','value'=>set_value('recipient', $recipient)))?>
				<?=form_error('recipient')?>
			</p>

			<p>
				<?=lang('cc', 'cc')?> 
				<?=form_input(array('id'=>'cc','name'=>'cc','class'=>'fullfield','value'=>set_value('cc', $cc)))?>
				<?=form_error('cc')?>
			</p>

			<p>
				<?=lang('bcc', 'bcc')?> 
				<?=form_input(array('id'=>'bcc','name'=>'bcc','class'=>'fullfield','value'=>set_value('bcc', $bcc)))?>
				<?=form_error('bcc')?>
			</p>

			<?php if (is_array($mailing_lists)):?>
			<h3><?=lang('send_to_mailinglist')?></h3>
			<ul class="shun">
				<?php foreach ($mailing_lists as $list => $details): ?>
				<li class="<?=alternator('even', 'odd')?>"><label><?=form_checkbox($details)?> &nbsp;<?=$list?></label></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>

			<?php if (is_array($member_groups)):?>
			<h3><?=lang('recipient_group')?></h3>
			<ul class="shun">
				<?php foreach ($member_groups as $group => $details): ?>
				<li class="<?=alternator('even', 'odd')?>"><label><?=form_checkbox($details)?> &nbsp;<?=$group?></label></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>

		<div id="communicate_compose">

			<p>
				<strong class="notice">*</strong> <?=lang('subject', 'subject')?> 
				<?=form_input(array('id'=>'subject','name'=>'subject','class'=>'fullfield','value'=>set_value('subject', $subject)))?>
				<?=form_error('subject')?>
			</p>

			<p style="margin-bottom:15px">
				<strong class="notice">*</strong> <?=lang('message', 'message')?><br />
				<?=form_error('message')?>
				<?=form_textarea(array('id'=>'message','name'=>'message','rows'=>20,'cols'=>85,'class'=>'fullfield','value'=>set_value('message', $message)))?>
			</p>
			<?php
			
			$this->table->set_template($cp_pad_table_template);
			$this->table->template['thead_open'] = '<thead class="visualEscapism">';
			
			$this->table->set_heading(
										array('data' => '&nbsp;', 'width' => '30%'),
										'&nbsp;'
									);
			if ($spell_enabled) 
			{
				$this->table->add_row(array(
						'<a href="#" class="spellcheck_link" id="spelltrigger_message" title="'.lang('check_spelling').'">'.lang('check_spelling').'</a>',
						build_spellcheck('message')
					)
				);						
			}
				
			$this->table->add_row(array(
					lang('mail_format', 'mailtype'),
					form_dropdown('mailtype', $mailtype_options, $mailtype, 'id="mailtype"').
					'<p class="" id="plaintext_alt_cont">'.
						lang('plaintext_alt', 'plaintext_alt').
						form_textarea(array(
									'id'		=> 'plaintext_alt',
									'name'		=> 'plaintext_alt', 
									'value'		=> set_value('plaintext_alt', $plaintext_alt), 
									'rows'		=> 8,
									'cols'		=> 80)).
					'</p>'
				)
			);
			
			$this->table->add_row(array(
					lang('text_formatting', 'text_fmt'),
					form_dropdown('text_fmt', $text_formatting_options, $text_fmt, 'id="text_fmt"')
				)
			);

			$this->table->add_row(array(
					lang('wordwrap', 'wordwrap'),
					form_dropdown('wordwrap', $word_wrap_options, $wordwrap, 'id="wordwrap"')
				)
			);
			
			$this->table->add_row(array(
					lang('priority', 'priority'),
					form_dropdown('priority', $priority_options,  $priority, 'id="priority"')
				)
			);
			
			$this->table->add_row(array(
					form_error('attachment').
					lang('attachment', 'attachment'),
					form_upload(array('id'=>'attachment','name'=>'attachment')).
					'<p>'.lang('attachment_warning').'</p>'
				)
			);
				
			echo $this->table->generate();
				
?>
			<p style="margin-top:15px;">
				<?php
				$accept_admin_email = array(
				  'name'        => 'accept_admin_email',
				  'id'          => 'accept_admin_email',
				  'value'       =>  'y',
				  'checked'		=> set_checkbox('accept_admin_email', 'y', $accept_admin_email)
				);
				echo form_checkbox($accept_admin_email);?> 
				
				<?=lang('honor_email_pref', 'accept_admin_email')?>
			</p>

			<p><strong class="notice">*</strong> <?=lang('required_fields')?></p>

			<p><?=form_submit(array('name' => 'submit', 'value' => lang('send_it'), 'class' => 'submit'))?>
		</div>

		<?=form_close()?>

		<div class="clear_right"></div>
	</div>