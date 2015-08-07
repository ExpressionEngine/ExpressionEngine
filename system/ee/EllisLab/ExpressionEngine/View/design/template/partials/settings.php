<?php // @TODO This needs localization! ?>
<div class="alert inline warn">
	<p><b>Warning</b>: Allowing PHP in templates has security implications.</p>
	<p class="caution">Any setting marked with <span title="excercise caution"></span> should be used with caution.</p>
</div>
<fieldset class="col-group required <?=form_error_class('template_name')?>">
	<div class="setting-txt col w-8 <?=form_error_class('template_name')?>">
		<h3><?=lang('template_name')?></h3>
		<em><?=lang('template_name_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input type="hidden" name="old_name" value="<?=$template->template_name?>">
		<input type="text" name="template_name" value="<?=set_value('template_name', $template->template_name)?>">
		<?=form_error('template_name')?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('template_type')?>">
	<div class="setting-txt col w-8 <?=form_error_class('template_type')?>">
		<h3><?=lang('template_type')?></h3>
		<em><?=lang('template_type_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<?=form_dropdown('template_type', $template_types, set_value('template_type', $template->template_type), FALSE)?>
		<?=form_error('template_type')?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('cache')?>">
	<div class="setting-txt col w-8 <?=form_error_class('cache')?>">
		<h3><?=lang('enable_caching')?></h3>
		<em><?=lang('enable_caching_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<?php $value = set_value('cache', $template->cache); ?>
		<label class="choice mr<?php if ($value == 'y' || $value === TRUE) echo ' chosen'?>"><input type="radio" name="cache" value="y"<?php if ($value == 'y' || $value === TRUE) echo ' checked="checked"'?>> <?=lang('enable')?></label>
		<label class="choice<?php if ($value == 'n' || $value === FALSE) echo ' chosen'?>"><input type="radio" name="cache" value="n"<?php if ($value == 'n' || $value === FALSE) echo ' checked="checked"'?>> <?=lang('disable')?></label>
		<?=form_error('cache')?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('refresh')?>">
	<div class="setting-txt col w-8 <?=form_error_class('refresh')?>">
		<h3><?=lang('refresh_interval')?></h3>
		<em><?=lang('refresh_interval_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input type="text" name="refresh" value="<?=set_value('refresh', $template->refresh)?>">
		<?=form_error('refresh')?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('allow_php')?>">
	<div class="setting-txt col w-8 <?=form_error_class('allow_php')?>">
		<h3 class="caution"><?=lang('enable_php')?> <span title="excercise caution"></span></h3>
		<em><?=lang('enable_php_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<?php $value = set_value('allow_php', $template->allow_php); ?>
		<label class="choice mr<?php if ($value == 'y' || $value === TRUE) echo ' chosen'?> yes"><input type="radio" name="allow_php" value="y"<?php if ($value == 'y' || $value === TRUE) echo ' checked="checked"'?>> <?=lang('yes')?></label>
		<label class="choice<?php if ($value == 'n' || $value === FALSE) echo ' chosen'?> no"><input type="radio" name="allow_php" value="n"<?php if ($value == 'n' || $value === FALSE) echo ' checked="checked"'?>> <?=lang('no')?></label>
		<?=form_error('allow_php')?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('php_parse_location')?>">
	<div class="setting-txt col w-8 <?=form_error_class('php_parse_location')?>">
		<h3><?=lang('parse_stage')?></h3>
		<em><?=lang('parse_stage_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<?php $value = set_value('php_parse_location', $template->php_parse_location); ?>
		<label class="choice mr<?php if ($value == 'i') echo ' chosen'?>"><input type="radio" name="php_parse_location" value="i"<?php if ($value == 'i') echo ' checked="checked"'?>> <?=lang('input')?></label>
		<label class="choice<?php if ($value == 'o') echo ' chosen'?>"><input type="radio" name="php_parse_location" value="o"<?php if ($value == 'o') echo ' checked="checked"'?>> <?=lang('output')?></label>
		<?=form_error('php_parse_location')?>
	</div>
</fieldset>
<fieldset class="col-group last <?=form_error_class('hits')?>">
	<div class="setting-txt col w-8 <?=form_error_class('hits')?>">
		<h3><?=lang('hit_counter')?></h3>
		<em><?=lang('hit_counter_desc')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input type="text" name="hits" value="<?=set_value('hits', $template->hits)?>">
		<?=form_error('hits')?>
	</div>
</fieldset>