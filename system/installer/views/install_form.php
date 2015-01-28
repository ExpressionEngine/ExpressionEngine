<?php if ( ! empty($errors)): ?>
	<div class="alert inline issue">
		<h3><?=lang('error_occurred')?></h3>
		<?php foreach ($errors as $error): ?>
			<p><?=$error?></p>
		<?php endforeach ?>
	</div>
<?php endif ?>

<div class="alert inline warn">
	<p><b>Please</b> read <a href="">Installing ExpressionEngine</a> <strong>before</strong> starting.</p>
</div>
<fieldset class="col-group">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_hostname')?> <span class="required" title="required field">&#10033;</span></h3>
		<em><?=lang('db_hostname_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_hostname" class="required" type="text" value="localhost">
	</div>
</fieldset>
<fieldset class="col-group">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_name')?> <span class="required" title="required field">&#10033;</span></h3>
		<em><?=lang('db_name_note')?></em>
		<em><mark><?=lang('db_name_warning')?></mark></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_name" class="required" type="text" value="">
	</div>
</fieldset>
<fieldset class="col-group">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_username')?> <span class="required" title="required field">&#10033;</span></h3>
		<em><?=lang('db_username_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_username" class="required" type="text" value="">
	</div>
</fieldset>
<fieldset class="col-group">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_password')?> <span class="required" title="required field">&#10033;</span></h3>
		<em><?=lang('db_password_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_password" class="required" type="password" value="">
	</div>
</fieldset>
<fieldset class="col-group last">
	<div class="setting-txt col w-8">
		<h3><?=lang('db_prefix')?> <span class="required" title="required field">&#10033;</span></h3>
		<em><?=lang('db_prefix_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="db_prefix" class="required" type="text" value="exp">
	</div>
</fieldset>
<h2><?=lang('default_theme')?></h2>
<fieldset class="col-group last">
	<div class="setting-txt col w-8">
		<h3><?=lang('install_default_theme')?></h3>
		<em><?=lang('install_default_theme_info')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<label class="choice mr yes"><input name="install_default_theme" value="y" type="radio"> <?=lang('yes')?></label>
		<label class="choice chosen no"><input name="install_default_theme" value="n" type="radio" checked="checked"> <?=lang('no')?></label>
	</div>
</fieldset>
<h2><?=lang('administrator_account')?></h2>
<fieldset class="col-group">
	<div class="setting-txt col w-8">
		<h3><?=lang('username')?> <span class="required" title="required field">&#10033;</span></h3>
		<em><?=lang('username_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="username" class="required" type="text" value="">
	</div>
</fieldset>
<fieldset class="col-group">
	<div class="setting-txt col w-8">
		<h3><?=lang('e_mail')?> <span class="required" title="required field">&#10033;</span></h3>
		<em><?=lang('e_mail_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="email_address" class="required" type="text" value="">
	</div>
</fieldset>
<fieldset class="col-group last">
	<div class="setting-txt col w-8">
		<h3><?=lang('password')?> <span class="required" title="required field">&#10033;</span></h3>
		<em><?=lang('password_note')?></em>
	</div>
	<div class="setting-field col w-8 last">
		<input name="password" class="required" type="password" value="">
	</div>
</fieldset>
<fieldset class="form-ctrls">
	<input class="btn" type="submit" value="<?=lang('start_installation')?>">
</fieldset>
