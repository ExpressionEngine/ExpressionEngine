<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="form-standard has-tabs publish" data-publish>
	<?=form_open($form_url, 'class="ajax-validate"')?>
	<div class="form-btns form-btns-top">
		<h1><?=$cp_page_title?></h1>
		<?php $this->embed('ee:_shared/form/buttons'); ?>
	</div>
	<div class="tab-wrap">
		<ul class="tabs layout">
			<?php foreach ($layout->getTabs() as $index => $tab): ?>
				<?php
				$icon = '';
				if (strpos($tab->id, 'custom_') !== FALSE)
				{
					$icon = '<span class="tab-remove">';
				}
				else
				{
					if ($tab->isVisible())
					{
						$icon = '<span class="tab-on">';
					}
					else
					{
						$icon = '<span class="tab-off">';
					}
				}
				?>
			<li><a<?php if ($index == 0): ?> class="act"<?php endif; ?> href="" rel="t-<?=$index?>"><?=lang($tab->title)?></a> <?php if ($tab->title != 'publish'): ?><?=$icon?></span><?php endif; ?></li>
			<?php endforeach; ?>
		</ul>
		<a class="add-tab m-link" rel="modal-add-new-tab" href="#"><?=lang('add_tab')?></a>
			<input type="hidden" name="field_layout" value='<?=json_encode($channel_layout->field_layout)?>'>
			<?=ee('CP/Alert')->get('layout-form')?>
			<?php foreach ($layout->getTabs() as $index => $tab): ?>
			<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
				<div class="layout-grid-wrap">
			<?php $fields = $tab->getFields();
			foreach ($fields as $field): ?>
				<div class="col-group">
					<div class="col w-16">
						<div class="layout-item">
							<span class="reorder"></span>
							<div class="field-instruct">
								<label><?=$field->getLabel()?> <span class="faded">(<?=$field->getTypeName()?>)</label>
								<div class="field-options">
									<?php if ($field->isRequired()): ?>
									<label class="field-option-required"><?=ucwords(lang('required_field'))?></label>
									<?php else: ?>
									<label class="field-option-hide"><input type="checkbox"<?php if ( ! $field->isVisible()): ?> checked="checked"<?php endif ?>><?=lang('hide')?></label>
									<?php endif; ?>
									<label class="field-option-collapse"><input type="checkbox"<?php if ($field->isCollapsed()):?> checked="checked"<?php endif ?>><?=lang('collapse')?></label>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
				</div>
			</div>
			<?php endforeach; ?>

			<?=$form?>

			<div class="form-btns">
				<?php $this->embed('ee:_shared/form/buttons'); ?>
			</div>
		</form>
	</div>
</div>

<?php ee('CP/Modal')->startModal('add-new-tab'); ?>
<div class="modal-wrap modal-add-new-tab hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="box">
					<h1><?=lang('add_tab')?> <span class="req-title"><?=lang('required_fields')?></h1>
					<form class="settings">
						<fieldset class="col-group required last">
							<div class="setting-txt col w-8">
								<h3><?=lang('tab_name')?></h3>
								<em><?=lang('tab_name_desc')?></em>
							</div>
							<div class="setting-field col w-8 last">
								<input type="text" name="tab_name" data-illegal="<?=lang('illegal_tab_name')?>" data-required="<?=lang('tab_name_required')?>" data-duplicate="<?=lang('duplicate_tab_name')?>">
							</div>
						</fieldset>
						<fieldset class="form-ctrls">
							<button class="btn"><?=lang('add_tab')?></button>
						</fieldset>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php ee('CP/Modal')->endModal(); ?>
