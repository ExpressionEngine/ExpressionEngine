<div class="box">
	<h1><?=($header) ?: $title?></h1>
	<div class="alert inline warn">
		<span class="icon-issue"></span>
		<div class="alert-content">
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
