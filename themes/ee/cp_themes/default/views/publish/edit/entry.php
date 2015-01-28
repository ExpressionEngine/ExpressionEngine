<?php extend_template('default-nav'); ?>

<div class="box has-tabs publish">
	<h1>
		<?=$cp_page_title?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span>
	</h1>
	<div class="tab-bar">
		<ul>
			<?php foreach ($layout as $index => $tab): ?>
			<li><a<?php if ($index == 0): ?> class="act"<?php endif; ?> href="" rel="t-<?=$index?>"><?=lang($tab['name'])?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?=form_open($form_url, $form_attributes, (isset($form_hidden)) ? $form_hidden : array())?>
		<?php foreach ($layout as $index => $tab): ?>
		<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
			<?php foreach ($tab['fields'] as $field): ?>
			<fieldset class="col-group">
				<div class="setting-txt col w-16">
					<h3><span class="ico sub-arrow"></span><?=$field->getLabel()?><?php if ($field->isRequired()): ?> <span class="required" title="required field">&#10033;</span><?php endif; ?></h3>
					<em><?=$field->getInstructions()?></em>
				</div>
				<div class="setting-field col w-16 last">
					<?=$field->getForm()?>
				</div>
			</fieldset>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
		<fieldset class="form-ctrls">
			<?=cp_form_submit(lang('btn_edit_entry'), lang('btn_edit_entry_working'))?>
		</fieldset>
	</form>
</div>
