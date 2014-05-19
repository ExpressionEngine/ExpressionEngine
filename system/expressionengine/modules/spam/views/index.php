<p>
	<?= form_open('C=addons_modules'. AMP . 'M=show_module_cp' . AMP . 'module=spam' . AMP. 'method=test'); ?>
		<input type='hidden' name='train' value='yes' />
		<?=form_submit(array('value'=>'Test Spam Filter','class'=>'submit'));?>
	</form>
</p>
<p>
	<?= form_open('C=addons_modules'. AMP . 'M=show_module_cp' . AMP . 'module=spam' . AMP. 'method=train'); ?>
		<input type='hidden' name='train' value='yes' />
		<?=form_submit(array('value'=>'Train Spam Filter','class'=>'submit'));?>
	</form>
</p>
