<?php extend_template('default-nav'); ?>

<div class="box has-tabs publish">
	<h1>
		<?=$cp_page_title?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span>
	</h1>
	<div class="tab-bar">
		<ul>
			<?php foreach ($layout->getTabs() as $index => $tab): ?>
			<li><a<?php if ($index == 0): ?> class="act"<?php endif; ?> href="" rel="t-<?=$index?>"><?=lang($tab->title)?></a> <span class="tab-remove"></span></li>
			<?php endforeach; ?>
		</ul>
		<a class="btn action add-tab" href="#"><?=lang('add_tab')?></a>
	</div>
	<?=form_open($form_url, 'class="settings ajax-validate"')?>
		<?=ee('Alert')->get('layout-form')?>
		<?php foreach ($layout->getTabs() as $index => $tab): ?>
		<div class="tab t-<?=$index?><?php if ($index == 0): ?> tab-open<?php endif; ?>">
		<?php foreach ($tab->getFields() as $field): ?>
			<fieldset class="col-group<?php if (end($tab->getFields()) == $field) echo' last'?>">
				<div class="layout-tools col w-2">
					<ul class="toolbar vertical">
						<li class="move"><a href=""></a></li>
						<?php if ( ! $field->isRequired()): ?>
						<li class="hide"><a href=""></a></li>
						<?php endif; ?>
					</ul>
				</div>
				<div class="setting-txt col w-14">
					<h3><span class="ico sub-arrow"></span><?=$field->getLabel()?><?php if ($field->isRequired()): ?> <span class="required" title="required field">&#10033;</span><?php endif; ?></h3>
					<em><?=$field->getType()?></em>
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
				<h3><?=lang('member_group(s)')?></h3>
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
						<input type="checkbox" name="member_groups[]" value="<?=$member_group->group_id?>"<?=$checked?>> <?=$member_group->group_title?>
					</label>
				<?php endforeach; ?>
			</div>
			<?=form_error('member_groups')?>
			</div>
		</fieldset>

		<fieldset class="form-ctrls">
			<?php if (ee()->form_validation->errors_exist()): ?>
			<button class="btn disable" disabled="disabled" name="submit" type="submit" value="create" data-submit-text="<?=lang('btn_create_layout')?>" data-work-text="<?=lang('btn_saving')?>"><?=lang('btn_fix_errors')?></button>
			<button class="btn disable" disabled="disabled" name="submit" type="submit" value="preview" data-submit-text="<?=lang('btn_preview_layout')?>" data-work-text="<?=lang('btn_saving')?>"><?=lang('btn_fix_errors')?></button>
			<?php else: ?>
			<button class="btn" name="submit" type="submit" value="create" data-submit-text="<?=lang('btn_create_layout')?>" data-work-text="<?=lang('btn_saving')?>"><?=lang('btn_create_layout')?></button>
			<button class="btn" name="submit" type="submit" value="preview" data-submit-text="<?=lang('btn_preview_layout')?>" data-work-text="<?=lang('btn_saving')?>"><?=lang('btn_preview_layout')?></button>
			<?php endif;?>
		</fieldset>
	</form>
</div>
