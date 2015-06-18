<div class="alert inline warn">
	<p><b>Please</b> read <a href="https://ellislab.com/expressionengine/user-guide/installation/installation.html" rel="external">Installing ExpressionEngine</a> <strong>before</strong> starting.</p>
</div>
<?php if ( ! empty($errors)): ?>
	<div class="alert inline issue">
		<h3><?=lang('error_occurred')?></h3>
		<?php foreach ($errors as $error): ?>
			<p><?=$error?></p>
		<?php endforeach ?>
	</div>
<?php endif ?>
<fieldset class="col-group required <?=form_error_class('db_hostname')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_hostname')?></h3>
		<em><?=lang('db_hostname_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_hostname" type="text" value="<?=set_value('db_hostname', 'localhost')?>">
		<?=form_error('db_hostname');?>
	</div>
</fieldset>
<fieldset class="col-group required <?=form_error_class('db_name')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_name')?></h3>
		<em><?=lang('db_name_note')?></em>
		<em><mark><?=lang('db_name_warning')?></mark></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_name" type="text" value="<?=set_value('db_name')?>">
		<?=form_error('db_name');?>
	</div>
</fieldset>
<fieldset class="col-group required <?=form_error_class('db_username')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_username')?></h3>
		<em><?=lang('db_username_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_username" type="text" value="<?=set_value('db_username')?>">
		<?=form_error('db_username');?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('db_password')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_password')?></h3>
		<em><?=lang('db_password_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_password" type="password" value="<?=set_value('db_password')?>">
		<?=form_error('db_password');?>
	</div>
</fieldset>
<fieldset class="col-group required <?=form_error_class('db_prefix')?> last">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_prefix')?></h3>
		<em><?=lang('db_prefix_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_prefix" type="text" value="<?=set_value('db_prefix', 'exp')?>">
		<?=form_error('db_prefix');?>
	</div>
</fieldset>
<h2><?=lang('default_theme')?></h2>
<fieldset class="col-group last">
	<div class="setting-txt col w-8">
		<h3><?=lang('install_default_theme')?></h3>
		<em><?=lang('install_default_theme_info')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<label class="choice mr yes"><input name="install_default_theme" value="y" type="radio" <?=set_radio('install_default_theme', 'y');?>> <?=lang('yes')?></label>
		<label class="choice chosen no"><input name="install_default_theme" value="n" type="radio" <?=set_radio('install_default_theme', 'n', TRUE);?>> <?=lang('no')?></label>
	</div>
</fieldset>
<h2><?=lang('administrator_account')?></h2>
<fieldset class="col-group required <?=form_error_class('username')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('username')?></h3>
		<em><?=lang('username_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="username" type="text" value="<?=set_value('username')?>">
		<?=form_error('username');?>
	</div>
</fieldset>
<fieldset class="col-group required <?=form_error_class('email_address')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('e_mail')?></h3>
		<em><?=lang('e_mail_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="email_address" type="text" value="<?=set_value('email_address')?>">
		<?=form_error('email_address');?>
	</div>
</fieldset>
<fieldset class="col-group required <?=form_error_class('password')?> last">
	<div class="setting-txt col w-8">
		<h3><?=lang('password')?></h3>
		<em><?=lang('password_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="password" type="password" value="">
		<?=form_error('password');?>
	</div>
</fieldset>
<fieldset class="form-ctrls">
	<input class="btn" type="submit" value="<?=lang('start_installation')?>">
</fieldset>
