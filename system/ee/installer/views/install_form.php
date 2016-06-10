<?php if ( ! empty($errors)): ?>
	<div class="alert inline issue">
		<h3><?=lang('error_occurred')?></h3>
		<?php foreach ($errors as $error): ?>
			<p><?=$error?></p>
		<?php endforeach ?>
	</div>
<?php endif ?>
<h2><?=lang('db_settings')?></h2>
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
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_name" type="text" value="<?=set_value('db_name')?>">
		<?=form_error('db_name');?>
	</div>
</fieldset>
<fieldset class="col-group required <?=form_error_class('db_username')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_username')?></h3>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_username" type="text" value="<?=set_value('db_username')?>">
		<?=form_error('db_username');?>
	</div>
</fieldset>
<fieldset class="col-group <?=form_error_class('db_password')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_password')?></h3>
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
		<input name="db_prefix" type="text" value="<?=set_value('db_prefix', 'exp')?>" maxlength="30">
		<?=form_error('db_prefix');?>
	</div>
</fieldset>
<h2><?=lang('default_theme')?></h2>
<fieldset class="col-group  <?=form_error_class('install_default_theme')?> last">
	<div class="setting-txt col w-8">
		<h3><?=lang('install_default_theme')?></h3>
		<em><?=lang('install_default_theme_info')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<label class="choice mr yes"><input name="install_default_theme" value="y" type="radio" <?=set_radio('install_default_theme', 'y');?>> <?=lang('yes')?></label>
		<label class="choice chosen no"><input name="install_default_theme" value="n" type="radio" <?=set_radio('install_default_theme', 'n', TRUE);?>> <?=lang('no')?></label>
		<?=form_error('install_default_theme');?>
	</div>
</fieldset>
<h2><?=lang('administrator_account')?></h2>
<fieldset class="col-group required <?=form_error_class('email_address')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('e_mail')?></h3>
	</div>
	<div class="setting-field col w-8 last">
		<input name="email_address" type="text" value="<?=set_value('email_address')?>">
		<?=form_error('email_address');?>
	</div>
</fieldset>
<fieldset class="col-group required <?=form_error_class('username')?>">
	<div class="setting-txt col w-8">
		<h3><?=lang('username')?></h3>
	</div>
	<div class="setting-field col w-8 last">
		<input name="username" type="text" value="<?=set_value('username')?>" maxlength="50">
		<?=form_error('username');?>
	</div>
</fieldset>
<fieldset class="col-group required <?=form_error_class('password')?> last">
	<div class="setting-txt col w-8">
		<h3><?=lang('password')?></h3>
	</div>
	<div class="setting-field col w-8 last">
		<input name="password" type="password" value="" maxlength="<?=PASSWORD_MAX_LENGTH?>">
		<?=form_error('password');?>
	</div>
</fieldset>
<fieldset class="form-ctrls <?=form_error_class('license_agreement')?>">
	<div class="password-req required">
		<div class="setting-txt col w-8">
			<h3><?=lang('license_agreement')?></h3>
		</div>
		<div class="setting-field col w-8 last">
			<label class="choice"><input type="checkbox" name="license_agreement" value="y" <?=set_checkbox('license_agreement', 'y')?>> yes</label>
			<?=form_error('license_agreement');?>
		</div>
	</div>
	<input class="btn" type="submit" value="<?=lang('start_installation')?>">
</fieldset>
