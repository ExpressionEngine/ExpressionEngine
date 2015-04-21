<?php extend_template('default-nav', 'outer_box'); ?>

<div class="box has-tabs publish">
	<h1>
		<?=$cp_page_title?> <span class="req-title"><?=lang('required_fields')?></span>
	</h1>
	<div class="tab-wrap layout">
		<ul class="tabs">
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
		<a class="btn action add-tab m-link" rel="modal-add-new-tab" href="#"><?=lang('add_tab')?></a>
		<?=form_open($form_url, 'class="settings ajax-validate"')?>
			<input type="hidden" name="field_layout" value="<?=json_encode($channel_layout->field_layout)?>">
			<?=ee('Alert')->get('layout-form')?>
			<?php foreach ($layout->getTabs() as $index => $tab): ?>
			<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
			<?php foreach ($tab->getFields() as $field): ?>
				<fieldset class="col-group sortable<?php if ($field->isRequired()) echo ' required'; ?><?php if (end($tab->getFields()) == $field) echo' last'?>">
					<div class="layout-tools col w-2">
						<ul class="toolbar vertical">
							<li class="move"><a href=""></a></li>
							<?php if ( ! $field->isRequired()): ?>
								<?php if ($field->isVisible()): ?>
									<li class="hide"><a href=""></a></li>
								<?php else: ?>
									<li class="unhide"><a href=""></a></li>
								<?php endif; ?>
							<?php endif; ?>
						</ul>
					</div>
					<div class="setting-txt col w-14">
						<h3<?php if ($field->isCollapsed()): ?> class="field-closed"<?php endif; ?>><span class="ico sub-arrow"></span><?=$field->getLabel()?></h3>
						<em<?php if ($field->isCollapsed()): ?> style="display: none"<?php endif; ?>><?=$field->getTypeName()?></em>
					</div>
				</fieldset>
			<?php endforeach; ?>
			</div>
			<?php endforeach; ?>

			<h2><?=lang('layout_options')?></h2>
			<fieldset class="col-group required">
				<div class="setting-txt col w-8">
					<h3><?=lang('name')?></h3>
					<em><?=lang('name_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<input type="text" name="layout_name" value="<?=set_value('route', $channel_layout->layout_name)?>">
					<?=form_error('layout_name')?>
				</div>
			</fieldset>
			<fieldset class="col-group required last">
				<div class="setting-txt col w-8">
					<h3><?=lang('member_group(s)')?></h3>
					<em><?=lang('member_group(s)_desc')?></em>
				</div>
				<div class="setting-field col w-8 last">
					<div class="scroll-wrap">
					<?php foreach ($member_groups as $member_group): ?>
						<?php
						$checked = '';
						$class = 'choice block';
						$disabled = '';
						$display = $member_group->group_title;

						if (in_array($member_group->group_id, $selected_member_groups))
						{
							$checked = ' checked="checked"';
							$class = 'choice block chosen';
						}

						if (isset($assigned_member_groups[$member_group->group_id]))
						{
							$layout = $assigned_member_groups[$member_group->group_id];
							$disabled = ' disabled="disabled"';
							$display = '<s>' . $display . '</s> <i>&mdash; ' . lang('assigned_to') . ' <a href="' . cp_url('channel/layout/edit/' . $layout->layout_id) . '">' . $layout->layout_name . '</a></i>';
						}
						?>
						<label class="<?=$class?>">
							<input type="checkbox" name="member_groups[]" value="<?=$member_group->group_id?>"<?=$checked?><?=$disabled?>> <?=$display?>
						</label>
					<?php endforeach; ?>
				</div>
				<?=form_error('member_groups')?>
				</div>
			</fieldset>

			<fieldset class="form-ctrls">
				<?=cp_form_submit($submit_button_text, lang('btn_saving'))?>
			</fieldset>
		</form>
	</div>
</div>

<?php $this->startOrAppendBlock('modals'); ?>
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
<?php $this->endBlock(); ?>