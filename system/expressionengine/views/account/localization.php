<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('localization')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=localization_update', '', $form_hidden)?>

	<p class="cf">
		<?=form_label(lang('timezone'), 'timezone')?>
		<span class="timezone">
			<?=$timezone_menu?>
		</span>
	</p>

	<p>
		<?=form_label(lang('time_format'), 'time_format')?>
		<?=form_dropdown('time_format', $time_format_options, $time_format, 'id="time_format"')?>
	</p>

	<p>
		<?=form_label(lang('language'), 'language')?>
		<?=form_dropdown('language', $language_options, $language, 'id="language"')?>
	</p>

	<p class="submit"><?=form_submit('localization_update', lang('update'), 'class="submit"')?></p>

	<?=form_close()?>
</div>