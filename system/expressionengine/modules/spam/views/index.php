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
<?php
$this->table->set_template(array(
	'table_open' => '<table class="mainTable" border="0" cellspacing="0" cellpadding="0">'
));
$this->table->set_heading(array(
	'Spam ID',
	'Content',
	'Moderate'
));
echo $this->table->generate($moderation);
?>
