<?php $this->extend('_templates/default-nav'); ?>

<div class="panel">
<div class="form-standard has-tabs publish" data-publish>
	<?=form_open($form_url, 'class="ajax-validate"')?>
  <div class="panel-heading">
    <div class="form-btns form-btns-top">
		<div class="title-bar title-bar--large">
    		<h3 class="title-bar__title"><?=ee('Format')->make('Text', (isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title)->attributeSafe()->compile()?></h3>

    		<div class="title-bar__extra-tools">
  				<?php $this->embed('ee:_shared/form/buttons'); ?>
			</div>
		</div>
  	</div>
  </div>
  <div class="panel-body">
	<div class="tab-wrap">
		<?=ee('CP/Alert')->get('layout-form')?>

		<?=$form?>

		<div class="field-instruct">
			<label><?=lang('tabs')?></label>
		</div>

		<div class="tab-bar tab-bar--editable layout">
			<div class="tab-bar__tabs">
			<?php foreach ($layout->getTabs() as $index => $tab): ?>
				<?php
                $icon = '';
                if (strpos($tab->id, 'custom_') !== false) {
                    $icon = '<i class="tab-remove">';
                } else {
                    if ($tab->isVisible()) {
                        $icon = '<i class="tab-on">';
                    } else {
                        $icon = '<i class="tab-off">';
                    }
                }
                ?>
				<button type="button" class="tab-bar__tab js-tab-button <?php if ($index == 0): ?>active<?php endif; ?>" rel="t-<?=$index?>"><?=lang($tab->title)?> <?php if ($tab->title != 'publish'): ?><?=$icon?></i><?php endif; ?></button>
			<?php endforeach; ?>
			</div>

			<a class="tab-bar__right-button button button--xsmall button--default m-link" rel="modal-add-new-tab" href="#"><?=lang('add_tab')?></a>
		</div>
			<input type="hidden" name="field_layout" value='<?=json_encode($channel_layout->field_layout)?>'>

			<?php foreach ($layout->getTabs() as $index => $tab): ?>
			<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
				<div class="layout-item-wrapper">
			<?php $fields = $tab->getFields();
            foreach ($fields as $field): ?>
						<div class="js-layout-item">
							<div class="layout-item">
								<div class="layout-item__handle ui-sortable-handle"></div>
								<div class="layout-item__content">
									<label class="layout-item__title"><span class="faded float-right"><?=$field->getTypeName()?></span><?=$field->getLabel()?> <span class="faded"><?=(($tab->id != 'categories') ? '(' . $field->getShortName() . ')' : '') ?></span></label>
									<div class="layout-item__options">
										<?php if ($field->isRequired()): ?>
										<label class="field-option-required"><?=ucwords(lang('required_field'))?></label>
										<?php else: ?>
										<label class="field-option-hide"><input class="checkbox checkbox--small" type="checkbox"<?php if (! $field->isVisible()): ?> checked="checked"<?php endif ?>><?=lang('hide')?></label>
										<?php endif; ?>
										<label class="field-option-collapse"><input class="checkbox checkbox--small" type="checkbox"<?php if ($field->isCollapsed()):?> checked="checked"<?php endif ?>><?=lang('collapse')?></label>
									</div>
								</div>
							</div>
						</div>
			<?php endforeach; ?>
				</div>
			</div>

			<?php endforeach; ?>
  </div>
  </div>
      <div class="panel-footer">
      <div class="form-btns">
				<?php $this->embed('ee:_shared/form/buttons'); ?>
			</div>
      </div>
		</form>


</div>
</div>

<?php ee('CP/Modal')->startModal('add-new-tab'); ?>
<div class="modal-wrap modal-add-new-tab hidden">
	<div class="modal modal--no-padding dialog">

          <div class="dialog__header">
            <h2 class="dialog__title"><?=lang('add_tab')?> <span class="req-title"><?=lang('required_fields')?></h2>
            <div class="dialog__close js-modal-close"><i class="fas fa-times"></i></div>
          </div>
          <div class="dialog__body">
					<form class="settings">
						<fieldset class="required">
              <div class="field-instruct">
                <label><?=lang('tab_name')?></label>
								<em><?=lang('tab_name_desc')?></em>
              </div>
							<div class="field-control">
								<input type="text" name="tab_name" data-illegal="<?=lang('illegal_tab_name')?>" data-required="<?=lang('tab_name_required')?>" data-duplicate="<?=lang('duplicate_tab_name')?>">
							</div>
						</fieldset>
          </div>
          <div class="dialog__actions dialog__actions--with-bg">
						<div class="dialog__buttons">
							<button class="button button--primary"><?=lang('add_tab')?></button>
						</div>
          </div>
					</form>
	</div>
</div>
<?php ee('CP/Modal')->endModal(); ?>
