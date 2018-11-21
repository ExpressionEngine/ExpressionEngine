<div class="box">
	<h1><?=($header) ?: $title?></h1>
	<div class="app-notice app-notice--inline app-notice---important">
		<div class="app-notice__tag">
			<span class="app-notice__icon"></span>
		</div>
		<div class="app-notice__content">
			<p><?=lang('install_note')?></p>
		</div>
	</div>
	<?php if ( ! empty($errors)): ?>
		<div class="app-notice app-notice--inline app-notice---error">
			<div class="app-notice__tag">
				<span class="app-notice__icon"></span>
			</div>
			<div class="app-notice__content">
				<?php foreach ($errors as $error): ?>
					<p><?=$error?></p>
				<?php endforeach ?>
			</div>
		</div>
	<?php endif ?>
	<form action="<?=$action?>" method="<?=$method?>">
		<?php if( ! is_null($utf8mb4_supported)): ?>
		<input type="hidden" name="utf8mb4_supported" value="n">
		<?php endif; ?>
		<fieldset class="<?=form_error_class('db_hostname')?>">
			<label><?=lang('db_hostname')?></label>
			<em><?=lang('db_hostname_note')?></em>
			<input name="db_hostname" type="text" autofocus="autofocus" value="<?=set_value('db_hostname', 'localhost')?>">
			<?=form_error('db_hostname')?>
		</fieldset>
		<fieldset class="<?=form_error_class('db_name')?>">
			<label><?=lang('db_name')?></label>
			<em><mark><?=lang('db_name_note')?></mark></em>
			<input name="db_name" type="text" value="<?=set_value('db_name')?>">
			<?=form_error('db_name')?>
		</fieldset>
		<fieldset class="<?=form_error_class('db_username')?>">
			<label><?=lang('db_username')?></label>
			<input name="db_username" type="text" value="<?=set_value('db_username')?>">
			<?=form_error('db_username')?>
		</fieldset>
		<fieldset class="col-group <?=form_error_class('db_password')?>">
			<label><?=lang('db_password')?></label>
			<input name="db_password" type="password" value="<?=set_value('db_password')?>">
			<?=form_error('db_password')?>
		</fieldset>
		<fieldset class="<?=form_error_class('db_prefix')?>">
			<label><?=lang('db_prefix')?></label>
			<em><?=lang('db_prefix_note')?></em>
			<input name="db_prefix" type="text" value="<?=set_value('db_prefix', 'exp')?>" maxlength="30">
			<?=form_error('db_prefix')?>
		</fieldset>
		<h2><?=lang('default_theme')?></h2>
		<fieldset class="<?=form_error_class('install_default_theme')?>">
			<label>
				<input type="checkbox" name="install_default_theme" value="y" <?=set_checkbox('install_default_theme', 'y')?>> <?=lang('install_default_theme')?>
				<?=form_error('install_default_theme')?>
			</label>
		</fieldset>
		<h2><?=lang('administrator_account')?></h2>
		<fieldset class="<?=form_error_class('email_address')?>">
			<label><?=lang('e_mail')?></label>
			<input name="email_address" type="text" value="<?=set_value('email_address')?>">
			<?=form_error('email_address')?>
		</fieldset>
		<fieldset class="<?=form_error_class('username')?>">
			<label><?=lang('username')?></label>
			<input name="username" type="text" value="<?=set_value('username')?>" maxlength="<?=USERNAME_MAX_LENGTH?>">
			<?=form_error('username')?>
		</fieldset>
		<fieldset class="<?=form_error_class('password')?> last">
			<label><?=lang('password')?></label>
			<input name="password" type="password" value="" maxlength="<?=PASSWORD_MAX_LENGTH?>">
			<?=form_error('password')?>
		</fieldset>
		<fieldset class="options <?=form_error_class('license_agreement')?>">
			<label><input type="checkbox" name="license_agreement" value="y" <?=set_checkbox('license_agreement', 'y')?>> <?=lang('license_agreement')?></label>
			<?=form_error('license_agreement')?>
		</fieldset>
		<fieldset class="options <?=form_error_class('share_analytics')?>">
			<label><input type="checkbox" name="share_analytics" value="y" <?=set_checkbox('share_analytics', 'y')?>> <?=lang('share_analytics')?></label>
			<em><?=lang('share_analytics_desc')?></em>
		</fieldset>
		<fieldset class="form-ctrls">
			<input class="btn" type="submit" value="<?=lang('start_installation')?>">
		</fieldset>
	</form>
</div>
