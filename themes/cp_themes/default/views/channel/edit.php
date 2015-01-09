<?php extend_template('default-nav'); ?>

<h1><?=$cp_page_title?> <span class="required intitle">&#10033; <?=lang('required_fields')?></span></h1>
<?=form_open(cp_url('channel/create'), 'class="settings ajax-validate"')?>
	<fieldset class="col-group <?=form_error_class('channel_title')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('channel_title')?> <span class="required" title="required field">&#10033;</span></h3>
			<em><?=lang('channel_title_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<input class="required" type="text" name="channel_title" value="<?=set_value('channel_title')?>">
			<?=form_error('channel_title')?>
		</div>
	</fieldset>
	<fieldset class="col-group <?=form_error_class('channel_name')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('channel_short_name')?> <span class="required" title="required field">&#10033;</span></h3>
			<em><?=lang('channel_short_name_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<input class="required" type="text" name="channel_name" value="<?=set_value('channel_name')?>">
			<?=form_error('channel_name')?>
		</div>
	</fieldset>
	<fieldset class="col-group last <?=form_error_class('duplicate_channel_prefs')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('channel_duplicate')?></h3>
			<em><?=lang('channel_duplicate_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?=form_dropdown('duplicate_channel_prefs', $duplicate_channel_prefs_options, set_value('duplicate_channel_prefs'))?>
			<?=form_error('duplicate_channel_prefs')?>
		</div>
	</fieldset>
	<h2><?=lang('channel_publishing_options')?></h2>
	<div class="alert inline warn">
		<?=lang('channel_publishing_options_warning')?>
	</div>
	<fieldset class="col-group <?=form_error_class('status_group')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('status_groups')?></h3>
			<em><?=lang('status_groups_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?php if (count($status_group_options) > 1): ?>
				<?=form_dropdown('status_group', $status_group_options, set_value('status_group'))?>
				<?=form_error('status_group')?>
			<?php else: ?>
				<div class="no-results">
					<p><?=lang('status_groups_not_found')?></p>
					<p><a class="btn action" href="<?=cp_url('channel/status/create')?>"><?=lang('create_new_status_group')?></a></p>
				</div>
			<?php endif ?>
		</div>
	</fieldset>
	<fieldset class="col-group <?=form_error_class('field_group')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('custom_field_group')?></h3>
			<em><?=lang('custom_field_group_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?php if (count($field_group_options) > 1): ?>
				<?=form_dropdown('field_group', $field_group_options, set_value('field_group'))?>
				<?=form_error('field_group')?>
			<?php else: ?>
				<div class="no-results">
					<p><?=lang('custom_field_groups_not_found')?></p>
					<p><a class="btn action" href="<?=cp_url('channel/groups/create')?>"><?=lang('create_new_field_group')?></a></p>
				</div>
			<?php endif ?>
		</div>
	</fieldset>
	<fieldset class="col-group last <?=form_error_class('cat_group')?>">
		<div class="setting-txt col w-8">
			<h3><?=lang('category_groups')?></h3>
			<em><?=lang('category_groups_desc')?></em>
		</div>
		<div class="setting-field col w-8 last">
			<?php if (count($cat_group_options) > 1): ?>
				<div class="scroll-wrap">
					<?php foreach ($cat_group_options as $group_id => $group_name): ?>
						<label class="choice block">
							<input type="checkbox" name="cat_group" value="<?=$group_id?>"> <?=$group_name?>
						</label>
					<?php endforeach ?>
					<?=form_error('cat_group')?>
				</div>
			<?php else: ?>
				<div class="no-results">
					<p><?=lang('category_groups_not_found')?></p>
					<p><a class="btn action" href="<?=cp_url('channel/cat/create')?>"><?=lang('create_new_category_group')?></a></p>
				</div>
			<?php endif ?>
		</div>
	</fieldset>

	<fieldset class="form-ctrls">
		<?=cp_form_submit(lang('create_channel'), lang('btn_saving'))?>
	</fieldset>
</form>