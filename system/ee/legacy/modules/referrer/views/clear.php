	<p><?=lang('total_referrers')?> <?=$total?></p>
	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=referrer'.AMP.'method=clear')?>

	<p>
		<?=lang('save_instructions', 'save')?> 
		<?=form_input('save', '100', 'id="save"')?>
	</p>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
	</p>

	<?=form_close()?>

