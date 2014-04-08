<?php extend_template('default-nav'); ?>

<div class="col w-12 last">
	<div class="box">
		<h1><?=$cp_page_title?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span></h1>
		<form class="settings">
			<fieldset class="col-group">
				<div class="setting-txt col w-16">
					<h3><?=lang('email_subject')?> <span class="required" title="required field">&#10033;</span></h3>
				</div>
				<div class="setting-field col w-16 last">
					<input class="required" type="text" value="">
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col w-16">
					<h3><?=lang('email_body')?> <span class="required" title="required field">&#10033;</span></h3>
				</div>
				<div class="setting-field col w-16 last">
					<textarea class="has-format-options required" cols="" rows=""></textarea>
					<div class="format-options">
						<label><?=lang('send_as')?></label>
						<select>
							<option>Plain Text</option>
							<option>Markdown</option>
							<option>HTML</option>
						</select>
						<label><?=lang('word_wrap')?></label>
						<input type="checkbox" checked="checked">
					</div>
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col w-8">
					<h3><?=lang('your_email')?> <span class="required" title="required field">&#10033;</span></h3>
					<em><?=lang('from_email')?>.</em>
				</div>
				<div class="setting-field col w-8 last">
					<input class="required" type="text" value="">
				</div>
			</fieldset>
			<fieldset class="col-group last">
				<div class="setting-txt col w-8">
					<h3><?=lang('attachment')?></h3>
					<em><?=lang('attachment_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<input type="file" value="">
				</div>
			</fieldset>
			<h2><?=lang('recipient_options')?></h2>
			<fieldset class="col-group">
				<div class="setting-txt col w-8">
					<h3><?=lang('primary_recipients')?></h3>
					<em><?=lang('primary_recipients_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<input type="text" value="">
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col w-8">
					<h3><?=lang('cc_recipients')?></h3>
					<em><?=lang('cc_recipients_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<input type="text" value="">
				</div>
			</fieldset>
			<fieldset class="col-group">
				<div class="setting-txt col w-8">
					<h3><?=lang('bcc_recipients')?></h3>
					<em><?=lang('bcc_recipients_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<input type="text" value="">
				</div>
			</fieldset>
			<fieldset class="col-group last">
				<div class="setting-txt col w-8">
					<h3><?=lang('add_member_groups')?></h3>
					<em><?=lang('add_member_groups_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<div class="scroll-wrap">
						<label class="choice block">
							<input type="checkbox"> Super Admins
						</label>
						<label class="choice block">
							<input type="checkbox"> Members
						</label>
						<label class="choice block">
							<input type="checkbox"> Pending
						</label>
					</div>
				</div>
			</fieldset>
			<fieldset class="form-ctrls">
				<input class="btn" type="submit" value="<?=lang('btn_send_email')?>" data-working-text="<?=lang('btn_send_email_working')?>">
				<input class="btn disable" type="submit" value="Fix Errors, Please">
			</fieldset>
		</form>
	</div>
</div>