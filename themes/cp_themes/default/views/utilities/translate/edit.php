<?php extend_template('default-nav'); ?>

<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
<?=form_open(cp_url('utilities/translate/' . $language . '/save/' . $file), 'class="settings"')?>
<?php $this->view('_shared/alerts')?>
<?php foreach ($keys as $key => $value):?>
	<fieldset class="col-group">
		<div class="setting-txt col w-8">
			<h3><?=$value['original']?></h3>
			<em></em>
		</div>
		<div class="setting-field col w-8 last">
			<input type="text" name="<?=$key?>" value="<?=$value['trans']?>">
		</div>
	</fieldset>

<?php endforeach;?>

	<fieldset class="form-ctrls">
		<?=cp_form_submit('translate_btn', 'translate_btn_working')?>
	</fieldset>
<?=form_close()?>