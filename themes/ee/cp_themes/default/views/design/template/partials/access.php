<fieldset class="col-group <?=form_error_class('allowed_member_groups')?>">
	<div class="setting-txt col w-8 <?=form_error_class('allowed_member_groups')?>">
		<h3><?=lang('allowed_member_groups')?></h3>
		<em><?=lang('allowed_member_groups_desc')?></em>
		<em><?=lang('allowed_member_groups_super_admin')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<div class="scroll-wrap">
			<?php foreach ($member_groups as $member_group):?>
				<?php
				if (in_array($member_group->group_id, $denied_member_groups))
				{
					$checked = '';
					$class = 'choice block';
				}
				else
				{
					$checked = ' checked="checked"';
					$class = 'choice block chosen';
				}
				?>
				<label class="<?=$class?>">
					<input type="checkbox" name="allowed_member_groups[]" value="<?=$member_group->group_id?>"<?=$checked?>> <?=$member_group->group_title?>
				</label>
			<?php endforeach; ?>
		</div>
		<?=form_error('allowed_member_groups')?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('no_auth_bounce')?>">
	<div class="setting-txt col w-8 <?=form_error_class('no_auth_bounce')?>">
		<h3><?=lang('non_access_redirect')?></h3>
		<em><?=lang('non_access_redirect_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<?=form_dropdown('no_auth_bounce', $existing_templates, set_value('no_auth_bounce', $template->no_auth_bounce), FALSE)?>
		<?=form_error('no_auth_bounce')?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('enable_http_auth')?>">
	<div class="setting-txt col w-8 <?=form_error_class('enable_http_auth')?>">
		<h3><?=lang('enable_http_authentication')?></h3>
		<em><?=lang('enable_http_authentication_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<?php $value = set_value('enable_http_auth', $template->enable_http_auth); ?>
		<label class="choice mr<?php if ($value == 'y' || $value === TRUE) echo ' chosen'?>"><input type="radio" name="enable_http_auth" value="y"<?php if ($value == 'y' || $value === TRUE) echo ' checked="checked"'?>> <?=lang('enable')?></label>
		<label class="choice<?php if ($value == 'n' || $value === FALSE) echo ' chosen'?>"><input type="radio" name="enable_http_auth" value="n"<?php if ($value == 'n' || $value === FALSE) echo ' checked="checked"'?>> <?=lang('disable')?></label>
		<?=form_error('enable_http_auth')?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('route')?>">
	<div class="setting-txt col w-8 <?=form_error_class('route')?>">
		<h3><?=lang('template_route_override')?></h3>
		<em><?=lang('template_route_override_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input type="text" name="route" value="<?=set_value('route', $route->route)?>">
		<?=form_error('route')?>
	</div>
</fieldset>
<fieldset class="col-group last <?=form_error_class('route_required')?>">
	<div class="setting-txt col w-8 <?=form_error_class('route_required')?>">
		<h3><?=lang('require_all_segments')?></h3>
		<em><?=lang('require_all_segments_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<?php $value = set_value('route_required', $route->route_required); ?>
		<label class="choice mr<?php if ($value == 'y' || $value === TRUE) echo ' chosen'?> yes"><input type="radio" name="route_required" value="y"<?php if ($value == 'y' || $value === TRUE) echo ' checked="checked"'?>> <?=lang('yes')?></label>
		<label class="choice<?php if ($value == 'n' || $value === FALSE) echo ' chosen'?> no"><input type="radio" name="route_required" value="n"<?php if ($value == 'n' || $value === FALSE) echo ' checked="checked"'?>> <?=lang('no')?></label>
		<?=form_error('route_required')?>
	</div>
</fieldset>