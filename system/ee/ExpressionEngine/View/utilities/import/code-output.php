<?php $this->extend('_templates/default-nav'); ?>

<h1><?=$cp_page_title?></h1>
<?=form_open(ee('CP/URL')->make('utilities/import-converter/download-xml'), 'class="settings"')?>
	<fieldset class="col-group last">
		<div class="setting-txt col w-16">
			<em>Generated from file: <i>(<?=$generated?> by <?=$username?>)</i></em>
		</div>
		<div class="setting-field col w-16 last">
			<textarea class="template-edit" name="xml" cols="" rows="">
<?=$code?>
			</textarea>
		</div>
	</fieldset>
	<fieldset class="form-ctrls">
		<input class="button button--primary" type="submit" value="<?=lang('btn_download_file')?>">
		<!--<a class="btn action" href="#"><?=lang('btn_copy_to_clipboard')?></a>-->
	</fieldset>
</form>