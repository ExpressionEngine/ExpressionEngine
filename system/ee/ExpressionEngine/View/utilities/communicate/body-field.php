<textarea class="has-format-options required" name="message" cols="" rows="10" aria-label="<?=lang('textarea_label')?>"><?=set_value('message', $message)?></textarea>
<?=form_error('message')?>
<div class="format-options">
	<label><?=lang('send_as')?></label>
	<?=form_dropdown('mailtype', $mailtype_options, $mailtype, 'id="mailtype"')?>
	<label class="checkbox-label" style="font-weight: 500; display: inline-block; margin-left: 5px;"><div class="checkbox-label__text" style="padding-left: 20px;"><?=lang('word_wrap')?></div>
	<input type="checkbox" class="checkbox" name="wordwrap" value="y" <?=set_checkbox('wordwrap', 'y', true)?>>
  </label>
</div>
