<div class="box">
	<div class="tbl-ctrls">
		<?=form_open(ee('CP/URL', 'addons/settings/forum'))?>
			<fieldset class="tbl-search right">
				<a class="btn tn action" href="<?=ee('CP/URL', 'addons/settings/forum/create/category')?>"><?=lang('new_category')?></a>
			</fieldset>
			<h1>[board_name] Forum listing<br><i>[board_short_name], <span class="yes"><?=lang('enabled')?></span></i></h1>

		<?=form_close();?>
	</div>
</div>