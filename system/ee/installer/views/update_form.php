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
		<fieldset class="form-ctrls">
			<input class="btn" type="submit" value="<?=lang('start_update')?>">
		</fieldset>
	</form>
</div>
