<?php extend_template('default-nav'); ?>

<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
<?php $this->view('_shared/form_messages')?>
<?=form_open(cp_url('utilities/translate/' . $language . '/save/' . $file), 'class="settings"')?>
<?php foreach ($keys as $key => $value):?>
	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?=$value['original']?></h3>
			<em></em>
		</div>
		<div class="setting-field col w-8">
			<input type="text" name="<?=$key?>" value="<?=$value['trans']?>">
		</div>
	</fieldset>

<?php endforeach;?>

	<fieldset class="form-ctrls">
		<input class="btn" type="submit" value="Save Translations">
		<input class="btn disable" type="submit" value="Fix Errors, Please">
		<input class="btn work" type="submit" value="Saving...">
	</fieldset>
<?=form_close()?>