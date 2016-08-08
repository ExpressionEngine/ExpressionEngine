<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box has-tabs publish">
	<h1>
		<?=$cp_page_title?> <span class="req-title"><?=lang('required_fields')?></span>
		<?php if ($entry->getAutosaves()->count()): ?>
		<div class="filters auto-save">
			<ul>
				<li>
					<a class="has-sub" href=""><?=lang('auto_saved_entries')?></a>
					<div class="sub-menu">
						<?php if ($entry->getAutosaves()->count() >= 10): ?>
						<fieldset class="filter-search">
							<input type="text" value="" data-fuzzy-filter="true" autofocus="autofocus" placeholder="<?=lang('filter_autosaves')?>">
						</fieldset>
						<?php endif; ?>
						<div class="scroll-wrap">
							<ul>
								<?php foreach ($entry->getAutosaves()->sortBy('edit_date') as $autosave): ?>
									<?php if ($entry->entry_id): ?>
									<li><a href="<?=ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id . '/' . $autosave->entry_id)?>"><?=ee()->localize->human_time($autosave->edit_date)?></a></li>
									<?php else: ?>
									<li><a href="<?=ee('CP/URL')->make('publish/create/' . $entry->Channel->channel_id . '/' . $autosave->entry_id)?>"><?=ee()->localize->human_time($autosave->edit_date)?></a></li>
									<?php endif;?>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</li>
			</ul>
		</div>
		<?php endif; ?>
	</h1>
	<div class="tab-wrap">
		<ul class="tabs">
			<?php
			foreach ($layout->getTabs() as $index => $tab):
				if ( ! $tab->isVisible()) continue;
				$class = '';

				if ($index == 0)
				{
					$class .= ' act';
				}

				if ($tab->hasErrors($errors))
				{
					$class .= ' invalid';
				}

				$class = trim($class);

				if ( ! empty($class))
				{
					$class = ' class="' . $class . '"';
				}
			?>
			<li><a<?=$class?> href="" rel="t-<?=$index?>"><?=lang($tab->title)?></a></li>
			<?php endforeach; ?>
		</ul>
		<?=form_open($form_url, $form_attributes, (isset($form_hidden)) ? $form_hidden : array())?>
			<?=ee('CP/Alert')->getAllInlines()?>
			<?php if ($extra_publish_controls): ?>
				<fieldset class="form-ctrls top">
					<?php
						$class = 'btn';
						$disabled = '';

						if ((isset($errors) && $errors->isNotValid()))
						{
							$class = 'btn disable';
							$disabled = 'disabled="disabled"';
						}

						$just_save = trim(sprintf(lang('btn_save'), ''));
					?>
					<button class="<?=$class?>" <?=$disabled?> name="submit" type="submit" value="edit" data-submit-text="<?=$just_save?>" data-work-text="<?=lang('btn_saving')?>"><?=($disabled) ? lang('btn_fix_errors') : $just_save?></button>
					<button class="<?=$class?>" <?=$disabled?> name="submit" type="submit" value="finish" data-submit-text="<?=lang('btn_save_and_close')?>" data-work-text="<?=lang('btn_saving')?>"><?=($disabled) ? lang('btn_fix_errors') : lang('btn_save_and_close')?></button>
				</fieldset>
			<?php endif ?>
			<?php foreach ($layout->getTabs() as $index => $tab): ?>
			<?php if ( ! $tab->isVisible()) continue; ?>
			<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
			<?=$tab->renderAlert()?>
			<?php foreach ($tab->getFields() as $field): ?>
			<?php if ( ! $field->isRequired() && ! $field->isVisible()) continue; ?>
				<?php
					switch ($field->getType())
					{
						case 'grid':
						case 'rte':
						case 'textarea':
							$width = "w-16";
							break;

						default:
							$width = "w-8";
							break;
					}

					if (($field->getType() == 'relationship' && $field->getSetting('allow_multiple'))
						OR $field->getSetting('field_wide'))
					{
						$width = "w-16";
					}

					$field_class = 'col-group';
					if ($field->getStatus() == 'warning')
					{
						$field_class .= ' warned';
					}
					if ($errors->hasErrors($field->getName()) && $field->getType() != 'grid')
					{
						$field_class .= ' invalid';
					}
					if ($field->isRequired())
					{
						$field_class .= ' required';
					}
					$fields = $tab->getFields();
					if (end($fields) == $field)
					{
						$field_class .= ' last';
					}
				?>
				<?php if ($field->getType() == 'grid'): ?>
				<div class="grid-publish <?=$field_class?>">
				<?php else: ?>
				<fieldset class="<?=$field_class?>">
				<?php endif; ?>
					<div class="setting-txt col <?=$width?><?php if ($errors->hasErrors($field->getName()) && $field->getType() == 'grid'):?> invalid<?php endif ?>">
						<h3<?php if ($field->isCollapsed()) echo ' class="field-closed"';?>><span class="ico sub-arrow"></span><?=$field->getLabel()?></h3>
						<em<?php if ($field->isCollapsed()) echo ' style="display: none;"';?>><?=$field->getInstructions()?></em>
						<?php if ($field->get('field_id') == 'categories' &&
								$entry->Channel->cat_group &&
								ee()->cp->allowed_group('can_create_categories')): ?>
							<p><a class="btn action submit m-link" rel="modal-checkboxes-edit" data-group-id="<?=$field->get('group_id')?>" href="#"><?=lang('btn_add_category')?></a></p>
						<?php endif; ?>
					</div>
					<div class="setting-field col <?=$width?> last"<?php if ($field->isCollapsed()) echo ' style="display: none;"';?>>
					<?php if ($field->get('field_id') == 'revisions'): ?>
						<?=$revisions?>
					<?php elseif ($field->getSetting('string_override') !== NULL): ?>
						<?=$field->getSetting('string_override')?>
					<?php else: ?>
						<?=$field->getForm()?>
						<?=$errors->renderError($field->getName())?>
					<?php endif; ?>
					</div>
				<?php if ($field->getType() == 'grid'): ?>
				</div>
				<?php else: ?>
				</fieldset>
				<?php endif; ?>
			<?php endforeach; ?>
			</div>
			<?php endforeach; ?>
			<fieldset class="form-ctrls">
				<?php
					$class = 'btn';
					$disabled = '';

					if ((isset($errors) && $errors->isNotValid()))
					{
						$class = 'btn disable';
						$disabled = 'disabled="disabled"';
					}

					$just_save = trim(sprintf(lang('btn_save'), ''));
				?>
				<button class="<?=$class?>" <?=$disabled?> name="submit" type="submit" value="edit" data-submit-text="<?=$just_save?>" data-work-text="<?=lang('btn_saving')?>"><?=($disabled) ? lang('btn_fix_errors') : $just_save?></button>
				<button class="<?=$class?>" <?=$disabled?> name="submit" type="submit" value="finish" data-submit-text="<?=lang('btn_save_and_close')?>" data-work-text="<?=lang('btn_saving')?>"><?=($disabled) ? lang('btn_fix_errors') : lang('btn_save_and_close')?></button>
			</fieldset>
		</form>
	</div>
</div>
