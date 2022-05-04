<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
	<?=form_open($form_url, 'class="settings"')?>
	<div class="panel-heading">
		<div class="form-btns form-btns-top">
			<div class="title-bar title-bar--large">
				<h3 class="title-bar__title"><?=$cp_page_title?></h3>
				<div class="title-bar__extra-tools">
					<?php $this->embed('ee:_shared/form/buttons'); ?>
				</div>
			</div>
		</div>
	</div>
	<div class="panel-body">
		<div class="single-templates-notice-wrapper">
			<?=ee('CP/Alert')->getAllInlines()?>
		</div>
		<fieldset class="col-group last">
			<div class="setting-txt col w-16">
				<em><?=sprintf(lang('last_edit'), ee()->localize->human_time($template->edit_date), $author)?></em>
			</div>
			<div class="setting-field col w-16 last">
				<textarea class="template-edit" cols="" rows="" name="template_data"><?=form_prep($template->template_data, 'template_data')?></textarea>
			</div>
		</fieldset>
	</div>

	<div class="panel-footer">
		<div class="form-btns">
			<?php $this->embed('ee:_shared/form/buttons'); ?>
		</div>
	</div>
	</form>
</div>
