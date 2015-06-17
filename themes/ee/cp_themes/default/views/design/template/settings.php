<h1><?=lang('template_settings_and_access')?></h1>
<div class="tab-bar">
	<ul>
		<li><a class="act" href="" rel="t-0"><?=lang('settings')?></a></li>
		<li><a href="" rel="t-1"><?=lang('access')?></a></li>
	</ul>
</div>
<?=form_open($form_url, 'class="settings ajax-validate"')?>
	<div class="tab t-0 tab-open">
		<?=$settings?>
	</div>
	<div class="tab t-1">
		<?=$access?>
	</div>
	<fieldset class="form-ctrls">
		<?=cp_form_submit('btn_save_settings', 'btn_saving')?>
	</fieldset>
</form>
