<?php $this->extend('_templates/default-nav'); ?>

<h1><?=$cp_page_title?></h1>
<?=form_open($form_url, 'class="settings"')?>
<?=ee('CP/Alert')->getAllInlines()?>
	<fieldset class="col-group last">
		<div class="setting-txt col w-16">
			<em><?=sprintf(lang('last_edit'), ee()->localize->human_time($template->edit_date), $author)?></em>
		</div>
		<div class="setting-field col w-16 last">
			<textarea class="template-edit" cols="" rows="" name="template_data"><?=set_value('template_data', $template->template_data)?></textarea>
		</div>
	</fieldset>
	<fieldset class="form-ctrls">
		<button class="btn" name="submit" type="submit" value="update" data-submit-text="<?=sprintf(lang('btn_save'), lang('template'))?>" data-work-text="<?=lang('btn_saving')?>"><?=sprintf(lang('btn_save'), lang('template'))?></button>
		<button class="btn" name="submit" type="submit" value="finish" data-submit-text="<?=lang('btn_update_and_finish_editing')?>" data-work-text="<?=lang('btn_saving')?>"><?=lang('btn_update_and_finish_editing')?></button>
	</fieldset>
</form>
