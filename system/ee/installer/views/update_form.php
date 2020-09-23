<div class="box">
	<h1><?=($header) ?: $title?></h1>
	<div class="app-notice app-notice--inline app-notice---important">
		<div class="app-notice__tag">
			<span class="app-notice__icon"></span>
		</div>
		<div class="app-notice__content">
			<p><?=lang('update_note')?></p>
			<p><?=lang('update_backup')?></p>
		</div>
	</div>
	<form action="<?=$action?>" method="post">
		<?php if($show_advanced): ?>
			<fieldset class="form-ctrls">
				<label><input type="checkbox" name="database_backup" value="1"> <?=lang('update_should_get_database_backup')?></label>
			</fieldset>
			<fieldset class="form-ctrls">
				<label><input type="checkbox" name="update_addons" value="1"> <?=lang('update_should_update_addons')?></label>
			</fieldset>
		<?php endif; ?>
		<fieldset class="form-ctrls">
			<input class="btn" type="submit" value="<?=lang('start_update')?>">
		</fieldset>
	</form>
</div>
