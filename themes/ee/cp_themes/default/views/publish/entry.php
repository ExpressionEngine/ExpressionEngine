<?php extend_template('default-nav'); ?>

<div class="box has-tabs publish">
	<h1>
		<?=$cp_page_title?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span>
		<?php if ($entry->getAutosaves()->count()): ?>
		<div class="filters auto-save">
			<ul>
				<li>
					<a class="has-sub" href=""><?=lang('auto_saved_entries')?></a>
					<div class="sub-menu">
						<fieldset class="filter-search">
							<input type="text" value="" placeholder="<?=lang('filter_autosaves')?>">
						</fieldset>
						<ul>
							<?php foreach ($entry->getAutosaves()->sortBy('edit_date') as $autosave): ?>
								<li><a href="<?=cp_url('publish/edit/entry/' . $entry->entry_id . '/' . $autosave->entry_id)?>"><?=ee()->localize->human_time($autosave->edit_date)?></a></li>
							<?php endforeach; ?>
						</ul>
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
			<?=ee('Alert')->getAllInlines()?>
			<?php foreach ($layout->getTabs() as $index => $tab): ?>
			<?php if ( ! $tab->isVisible()) continue; ?>
			<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
			<?php foreach ($tab->getFields() as $field): ?>
			<?php if ( ! $field->isVisible()) continue; ?>
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

					if ($field->getType() == 'relationship' && $field->getSetting('allow_multiple'))
					{
						$width = "w-16";
					}

					$field_class = 'col-group';
					if (end($tab->getFields()) == $field)
					{
						$field_class .= ' last';
					}
				?>
				<?php if ($field->getType() == 'grid'): ?>
				<div class="grid-publish <?=$field_class?>">
				<?php else: ?>
				<fieldset class="<?=$field_class?><?php if ($errors->hasErrors($field->getName())) echo ' invalid'; ?>">
				<?php endif; ?>
					<div class="setting-txt col <?=$width?>">
						<h3><span class="ico sub-arrow"></span><?=$field->getLabel()?><?php if ($field->isRequired()): ?> <span class="required" title="<?=lang('required_field')?>">&#10033;</span><?php endif; ?></h3>
						<em><?=$field->getInstructions()?></em>
						<?php if ($field->getName() == 'categories'): ?>
						<p><a class="btn action submit m-link" rel="modal-cats" href="#"><?=lang('btn_add_category')?></a></p>
						<?php endif; ?>
					</div>
					<div class="setting-field col <?=$width?> last">
						<?=$field->getForm()?>
						<?=$errors->renderError($field->getName())?>
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
				<?=cp_form_submit($button_text, lang('btn_saving'))?>
			</fieldset>
		</form>
	</div>
</div>
