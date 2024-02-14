<div class="panel">
<div class="form-standard" data-publish>
    <?=form_open($form_url, $form_attributes, (isset($form_hidden)) ? $form_hidden : array())?>

    <?php if (!isset($pro_class)) : ?>
    <div class="panel-heading panel-heading__publish">
        <div class="title-bar title-bar--large">
            <h3 class="title-bar__title">
                <?=$head['title']?>
                <?php if (isset($version)) {
                    $this->embed('ee:publish/partials/revision_badge', $version);
                } ?>
            </h3>
        </div>
    </div>
    <?php endif; ?>

    <div class="panel-body panel-body__publish">

    <div class="tab-wrap">

        <?php if (!isset($pro_class)) : ?>
        <div class="title-bar__extra-tools title-bar__extra-tools-publish tab-bar__right-buttons">
            <div class="form-btns"><?php $this->embed('ee:_shared/form/buttons'); ?></div>
        </div>
        <?php endif; ?>
        <div class="tab-bar tab-bar--sticky<?php if (isset($pro_class)) : ?> hidden<?php endif; ?>">
            <?php if (!isset($pro_class)) : ?>
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
            <?php if ($entry->getAutosaves()->filter('channel_id', $entry->channel_id)->count()): ?>
                <button type="button" class="tab-bar__tab js-tab-button" rel="t-autosaves"><?=lang('autosaves')?></button>
            <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($pro_class)) : ?>
            <div class="tab-bar__right-buttons">
                <div class="form-btns"><?php $this->embed('ee:_shared/form/buttons'); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?=ee('CP/Alert')->getAllInlines(isset($pro_class) ? 'error' : null)?>
        <?php foreach ($layout->getTabs() as $index => $tab):
            if (ee('Request')->get('field_id') != '') {
                $tabIsHidden = true;
                foreach ($tab->getFields() as $field) {
                    if ($field->getId() == ee('Request')->get('field_id')) {
                        $tabIsHidden = false;
                        continue;
                    }
                }
                if ($tabIsHidden) {
                    continue;
                }
            }

            if (! $tab->isVisible()) {
                continue;
            } ?>
        <div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
        <?=$tab->renderAlert()?>
        <?php foreach ($tab->getFields() as $field): ?>
            <?php if (ee('Request')->get('field_id') != '') {
                if ($field->getId() != ee('Request')->get('field_id')) {
                    continue;
                }
            }
            ?>
            <?php if (! $field->isRequired() && ! $field->isVisible()) {
                continue;
            } ?>
            <?=$field->renderAlert()?>
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
                if ($field->isCollapsed() && !isset($pro_class)) {
                    $field_class .= ' fieldset---closed';
                }
                if ($field->isConditional() && $field->isConditionallyHidden()) {
                    $field_class .= ' hide-block';
                }
            ?>
            <?php if ($field->getType() == 'grid' || $field->getType() == 'file_grid'): ?>
            <div class="fieldset-faux <?=$field_class?>"  data-field_id="<?=$field->getId()?>" <?php if (!isset($pro_class)) : ?> style="width:<?php echo $field->getWidth()?>%" <?php endif; ?>>
            <?php else: ?>
            <fieldset class="<?=$field_class?>" data-field_id="<?=$field->getId()?>" <?php if (!isset($pro_class)) : ?> style="width:<?php echo $field->getWidth()?>%" <?php endif; ?>>
            <?php endif; ?>
                <div class="field-instruct">
                    <?php if (! $field->titleIsHidden()):?>
                        <label><?php if (!isset($pro_class)) : ?><span class="ico sub-arrow js-toggle-field"></span><?php endif; ?><?=$field->getLabel()?></label>
                        <?=$field->getNameBadge()?>
                        <?php
                        $fieldInstructions = $field->getInstructions();
                        if (!empty($fieldInstructions)) :?>
                        <em><?=$fieldInstructions?></em>
                        <?php endif;?>
                    <?php endif;?>
                </div>
                <div class="field-control">
                    <?php if ($field->get('field_id') == 'revisions'): ?>
                        <div class="panel panel__with-border">
                            <?=$revisions?>
                        </div>
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
        <?php if (!isset($pro_class)) : ?>
        <div class="tab t-autosaves">
            <fieldset>
                <div class="field-instruct<?=$field_class?>">
                    <label><span class="ico sub-arrow js-toggle-field"></span><?=lang('autosaved_versions')?></label>
                    <em><?=lang('autosaved_versions_desc')?></em>
                </div>
                <div class="field-control">
                  <div class="panel">
                    <?=$autosaves?>
                  </div>
                </div>
            </fieldset>
        </div>
        <?php endif; ?>
  </div>

</div>
    </form>
</div>
</div>
<?=ee('CP/Alert')->getStandard()?>
