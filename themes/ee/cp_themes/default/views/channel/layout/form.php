<?php extend_template('default-nav'); ?>

<div class="box has-tabs publish">
	<h1>
		<?=$cp_page_title?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span>
	</h1>
	<div class="tab-bar layout">
		<ul>
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
		<a class="btn action add-tab" href="#"><?=lang('add_tab')?></a>
	</div>
	<?=form_open($form_url, 'class="settings ajax-validate"')?>
		<input type="hidden" name="field_layout" value="<?=json_encode($channel_layout->field_layout)?>">
		<?=ee('Alert')->get('layout-form')?>
		<?php foreach ($layout->getTabs() as $index => $tab): ?>
		<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
		<?php foreach ($tab->getFields() as $field): ?>
			<fieldset class="col-group sortable<?php if (end($tab->getFields()) == $field) echo' last'?>">
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
					<h3<?php if ($field->isCollapsed()): ?> class="field-closed"<?php endif; ?>><span class="ico sub-arrow"></span><?=$field->getLabel()?><?php if ($field->isRequired()): ?> <span class="required" title="required field">&#10033;</span><?php endif; ?></h3>
					<em<?php if ($field->isCollapsed()): ?> style="display: none"<?php endif; ?>><?=$field->getType()?></em>
				</div>
			</fieldset>
		<?php endforeach; ?>
		</div>
		<?php endforeach; ?>

		<h2><?=lang('layout_options')?></h2>
		<fieldset class="col-group">
			<div class="setting-txt col w-8">
				<h3><?=lang('name')?> <span class="required" title="required field">&#10033;</span></h3>
				<em><?=lang('name_desc')?></em>
			</div>
			<div class="setting-field col w-8 last">
				<input class="required" type="text" name="layout_name" value="<?=set_value('route', $channel_layout->layout_name)?>">
				<?=form_error('layout_name')?>
			</div>
		</fieldset>
		<fieldset class="col-group last">
			<div class="setting-txt col w-8">
				<h3><?=lang('member_group(s)')?> <span class="required" title="required field">&#10033;</span></h3>
				<em><?=lang('member_group(s)_desc')?></em>
			</div>
			<div class="setting-field col w-8 last">
				<div class="scroll-wrap">
				<?php foreach ($member_groups as $member_group):?>
					<?php
					if (in_array($member_group->group_id, $selected_member_groups))
					{
						$checked = ' checked="checked"';
						$class = 'choice block chosen';
					}
					else
					{
						$checked = '';
						$class = 'choice block';
					}
					?>
					<label class="<?=$class?>">
						<input type="checkbox" name="member_groups[]" value="<?=$member_group->group_id?>"<?=$checked?> class="required"> <?=$member_group->group_title?>
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
