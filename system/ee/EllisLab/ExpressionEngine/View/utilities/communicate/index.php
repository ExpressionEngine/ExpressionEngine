<?php $this->extend('_templates/default-nav'); ?>

<h1><?=$cp_page_title?> <span class="req-title"><?=lang('required_fields')?></span></h1>
<?=form_open_multipart(ee('CP/URL')->make('utilities/communicate/send'), 'class="settings"')?>
	<?=ee('CP/Alert')->getAllInlines()?>
	<fieldset class="col-group required <?=form_error_class('subject')?>">
		<div class="setting-txt col w-16">
			<h3><?=lang('email_subject')?></h3>
		</div>
		<div class="setting-field col w-16 last">
			<input type="text" name="subject" value="<?=set_value('subject', $subject)?>">
			<?=form_error('subject')?>
		</div>
	</fieldset>
	<fieldset class="col-group required <?=form_error_class('message')?>">
		<div class="setting-txt col w-16">
			<h3><?=lang('email_body')?></h3>
		</div>
		<div class="setting-field col w-16 last">
			<textarea class="has-format-options required" name="message" cols="" rows=""><?=set_value('message', $message)?></textarea>
			<?=form_error('message')?>
			<div class="format-options">
				<label><?=lang('send_as')?></label>
				<?=form_dropdown('mailtype', $mailtype_options, $mailtype, 'id="mailtype"')?>
				<label><?=lang('word_wrap')?></label>
				<input type="checkbox" name="wordwrap" value="y" <?=set_checkbox('wordwrap', 'y', TRUE)?>>
			</div>
		</div>
	</fieldset>
	<fieldset class="col-group required">
		<div class="setting-txt col w-16">
			<h3><?=lang('plaintext_body')?></h3>
			<em><?=lang('plaintext_alt')?></em>
		</div>
		<div class="setting-field col w-16 last">
			<textarea name="plaintext_alt" cols="" rows=""><?=set_value('plaintext_alt', $plaintext_alt)?></textarea>
		</div>
	</fieldset>
	<fieldset class="col-group required <?=form_error_class('from')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('your_email')?></h3>
		</div>
		<div class="setting-field col w-8 last">
			<input type="text" name="from" value="<?=set_value('from', $from)?>">
			<?=form_error('from')?>
		</div>
	</fieldset>
	<fieldset class="col-group last <?=form_error_class('attachment')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('attachment')?></h3>
			<em><?=lang('attachment_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<input type="file" name="attachment">
			<?=form_error('attachment')?>
		</div>
	</fieldset>
	<h2><?=lang('recipient_options')?></h2>
	<fieldset class="col-group <?=form_error_class('recipient')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('primary_recipients')?></h3>
			<em><?=lang('primary_recipients_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<input type="text" name="recipient" value="<?=set_value('recipient', $recipient)?>">
			<?=form_error('recipient')?>
		</div>
	</fieldset>
	<fieldset class="col-group <?=form_error_class('cc')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('cc_recipients')?></h3>
			<em><?=lang('cc_recipients_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<input type="text" name="cc" value="<?=set_value('cc', $cc)?>">
			<?=form_error('cc')?>
		</div>
	</fieldset>
	<fieldset class="col-group <?=form_error_class('bcc')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('bcc_recipients')?></h3>
			<em><?=lang('bcc_recipients_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<input type="text" name="bcc" value="<?=set_value('bcc', $bcc)?>">
			<?=form_error('bcc')?>
		</div>
	</fieldset>
	<?php if ($member_groups !== FALSE): ?>
	<fieldset class="col-group last">
		<div class="setting-txt col w-8">
			<h3><?=lang('add_member_groups')?></h3>
			<em><?=lang('add_member_groups_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<div class="scroll-wrap">
				<?php foreach ($member_groups as $group => $details): ?>
				<label class="choice block">
					<?=form_checkbox($details['attrs'])?> <?=$group?> (<?=$details['members']?>)
				</label>
				<?php endforeach; ?>
			</div>
		</div>
	</fieldset>
	<?php endif ?>
	<fieldset class="form-ctrls">
		<?=cp_form_submit('btn_send_email', 'btn_send_email_working')?>
	</fieldset>
</form>
