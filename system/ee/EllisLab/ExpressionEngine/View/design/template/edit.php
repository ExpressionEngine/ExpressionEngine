<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box has-tabs">
	<h1><?=$cp_page_title?> <a class="btn action ta" href="<?=$view_path?>" rel="external"><?=lang('view_rendered')?></a></h1>
	<div class="tab-wrap">
		<ul class="tabs">
			<li><a class="act" href="" rel="t-0"><?=lang('edit')?></a></li>
			<li><a href="" rel="t-1"><?=lang('notes')?></a></li>
			<li><a href="" rel="t-2"><?=lang('settings')?></a></li>
			<li><a href="" rel="t-3"><?=lang('access')?></a></li>
			<?php if ($revisions): ?><li><a href="" rel="t-4"><?=lang('revisions')?></a></li><?php endif ?>
		</ul>
		<?=form_open($form_url, 'class="settings ajax-validate"')?>
		<?=ee('CP/Alert')->get('template-form')?>
			<div class="tab t-0 tab-open">
				<fieldset class="col-group last">
					<div class="setting-txt col w-16">
						<em><?=sprintf(lang('last_edit'), ee()->localize->human_time($template->edit_date), $author)?></em>
					</div>
					<div class="setting-field col w-16 last">
						<textarea class="template-edit" cols="" rows="" name="template_data"><?=set_value('template_data', $template->template_data)?></textarea>
					</div>
				</fieldset>
			</div>
			<div class="tab t-1">
				<fieldset class="col-group last">
					<div class="setting-txt col w-16">
						<h3><?=lang('template_notes')?></h3>
						<em><?=lang('template_notes_desc')?></em>
					</div>
					<div class="setting-field col w-16 last">
						<textarea cols="" rows="" name="template_notes"><?=set_value('template_notes', $template->template_notes)?></textarea>
					</div>
				</fieldset>
			</div>
			<div class="tab t-2">
				<?=$settings?>
			</div>
			<div class="tab t-3">
				<?=$access?>
			</div>
			<?php if ($revisions): ?>
				<div class="tab t-4">
					<?=$revisions?>
				</div>
			<?php endif ?>
			<fieldset class="form-ctrls">
				<?php if (ee()->form_validation->errors_exist()): ?>
				<button class="btn disable" disabled="disabled" name="submit" type="submit" value="update" data-submit-text="<?=sprintf(lang('btn_save'), lang('template'))?>" data-work-text="<?=lang('btn_update_template_working')?>"><?=lang('btn_fix_errors')?></button>
				<button class="btn disable" disabled="disabled" name="submit" type="submit" value="finish" data-submit-text="<?=lang('btn_update_and_finish_editing')?>" data-work-text="<?=lang('btn_update_template_working')?>"><?=lang('btn_fix_errors')?></button>
				<?php else: ?>
				<button class="btn" name="submit" type="submit" value="update" data-submit-text="<?=sprintf(lang('btn_save'), lang('template'))?>" data-work-text="<?=lang('btn_update_template_working')?>"><?=sprintf(lang('btn_save'), lang('template'))?></button>
				<button class="btn" name="submit" type="submit" value="finish" data-submit-text="<?=lang('btn_update_and_finish_editing')?>" data-work-text="<?=lang('btn_update_template_working')?>"><?=lang('btn_update_and_finish_editing')?></button>
				<?php endif;?>
			</fieldset>
		</form>
	</div>
</div>
