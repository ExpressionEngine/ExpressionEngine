<div class="panel">
  <div class="panel-body">
<div class="form-standard" data-publish>
	<?=form_open($form_url, $form_attributes, (isset($form_hidden)) ? $form_hidden : array())?>

	<div class="tab-wrap">
		<div class="tab-bar tab-bar--sticky">
			<div class="tab-bar__tabs">
			<?php
            foreach ($layout->getTabs() as $index => $tab):
                if (! $tab->isVisible()) {
                    continue;
                }
                $class = '';

                if ($index == 0) {
                    $class .= ' active';
                }

                if ($tab->hasErrors($errors)) {
                    $class .= ' invalid';
                }
            ?>
			<button type="button" class="tab-bar__tab js-tab-button <?=$class?>" rel="t-<?=$index?>"><?=lang($tab->title)?></button>
			<?php endforeach; ?>
			<?php if ($entry->getAutosaves()->count()): ?>
				<button type="button" class="tab-bar__tab js-tab-button" rel="t-autosaves"><?=lang('autosaves')?></button>
			<?php endif ?>
			</div>

			<div class="tab-bar__right-buttons">
				<div class="form-btns"><?php $this->embed('ee:_shared/form/buttons'); ?></div>
			</div>
		</div>

		<?=ee('CP/Alert')->getAllInlines()?>
		<?php foreach ($layout->getTabs() as $index => $tab): ?>
		<?php if (! $tab->isVisible()) {
                continue;
            } ?>
		<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
		<?=$tab->renderAlert()?>
		<?php foreach ($tab->getFields() as $field): ?>
			<?php if (! $field->isRequired() && ! $field->isVisible()) {
                continue;
            } ?>
			<?php
                $field_class = '';
                if ($field->getStatus() == 'warning') {
                    $field_class .= ' warned';
                }
                if ($errors->hasErrors($field->getName())
                    && $field->getType() != 'grid'
                    && $field->getType() != 'file_grid') {
                    $field_class .= ' fieldset-invalid';
                }
                if ($field->isRequired()) {
                    $field_class .= ' fieldset-required';
                }
                if ($field->isCollapsed()) {
                    $field_class .= ' fieldset---closed';
                }
            ?>
			<?php if ($field->getType() == 'grid' || $field->getType() == 'file_grid'): ?>
			<div class="fieldset-faux <?=$field_class?>">
			<?php else: ?>
			<fieldset class="<?=$field_class?>">
			<?php endif; ?>
				<div class="field-instruct">
					<label><span class="ico sub-arrow js-toggle-field"></span><?=$field->getLabel()?></label>
					<?php
                    $fieldInstructions = $field->getInstructions();
                    if (!empty($fieldInstructions)) : ?>
					<em><?=$fieldInstructions?></em>
					<?php endif; ?>
				</div>
				<div class="field-control">
					<?php if ($field->get('field_id') == 'revisions'): ?>
						<?=$revisions?>
					<?php elseif ($field->getSetting('string_override') !== null): ?>
						<?=$field->getSetting('string_override')?>
					<?php else: ?>
						<?=$field->getForm()?>
						<?=$errors->renderError($field->getName())?>
					<?php endif; ?>
				</div>
			<?php if ($field->getType() == 'grid' || $field->getType() == 'file_grid'): ?>
			</div>
			<?php else: ?>
			</fieldset>
			<?php endif; ?>
		<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
		<div class="tab t-autosaves">
			<fieldset>
				<div class="field-instruct<?=$field_class?>">
					<label><span class="ico sub-arrow js-toggle-field"></span><?=lang('autosaved_versions')?></label>
					<em><?=lang('autosaved_versions')?></em>
				</div>
				<div class="field-control">
					<?=$autosaves?>
				</div>
			</fieldset>
		</div>
  </div>

	</form>
</div>
</div>
</div>
<?=ee('CP/Alert')->getStandard()?>
