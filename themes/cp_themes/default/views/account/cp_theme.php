<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('cp_theme')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=save_theme', '', $form_hidden)?>

	<p>
		<?=lang('choose_theme', 'cp_theme')?>
		<?=form_dropdown('cp_theme', $cp_theme_options, $cp_theme, 'id="cp_theme"')?>
	</p>

	<p class="submit"><?=form_submit('save_theme', lang('update'), 'class="submit"')?></p>

	<?=form_close()?>
</div>