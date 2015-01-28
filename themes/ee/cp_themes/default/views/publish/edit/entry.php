<?php extend_template('default-nav'); ?>

<div class="box has-tabs publish">
	<h1>
		<?=$cp_page_title?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span>
	</h1>
	<div class="tab-bar">
		<ul>
			<li><a class="act" href="" rel="t-0"><?=lang('publish')?></a></li>
			<li><a href="" rel="t-1"><?=lang('date')?></a></li>
			<li><a href="" rel="t-2"><?=lang('categories')?></a></li>
			<li><a href="" rel="t-3"><?=lang('options')?></a></li>
			<li><a href="" rel="t-4"><?=lang('pages')?></a></li>
		</ul>
	</div>
	<?=form_open($form_url, $form_attributes, (isset($form_hidden)) ? $form_hidden : array())?>
		<div class="tab t-0 tab-open">
			<?php foreach ($entry->getCustomFields() as $field): ?>
			<fieldset class="col-group">
				<div class="setting-txt col w-16">
					<h3><span class="ico sub-arrow"></span><?=$field->type_info->field_label?><?php if ($field->type_info->field_required == 'y'): ?> <span class="required" title="required field">&#10033;</span><?php endif; ?></h3>
					<em><?=$field->type_info->field_instructions?></em>
				</div>
				<div class="setting-field col w-16 last">
					<?=$field->getForm()?>
				</div>
			</fieldset>
			<?php endforeach; ?>
		</div>
		<div class="tab t-1">
		</div>
		<div class="tab t-2">
		</div>
		<div class="tab t-3">
		</div>
		<div class="tab t-4">
		</div>
		<fieldset class="form-ctrls">
			<?=cp_form_submit(lang('btn_edit_entry'), lang('btn_edit_entry_working'))?>
		</fieldset>
	</form>
</div>
