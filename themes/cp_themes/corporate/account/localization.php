<?php $this->load->view('account/_account_header');?>

	<div>
		<h3><?=lang('localization')?></h3>

		<?=form_open('C=myaccount'.AMP.'M=localization_update', '', $form_hidden)?>

		<p>
			<?=form_label(lang('timezone'), 'timezone')?>
			<?=timezone_menu($timezone)?>
		</p>

		<p>
			<?=lang('daylight_savings_time', 'daylight_savings_time')?><br />
			<?=form_radio('daylight_savings', 'y', $daylight_savings_y, 'id="daylight_savings_y"')?> <?=lang('yes').NBS.NBS.NBS.NBS.NBS ?>
			<?=form_radio('daylight_savings', 'n', $daylight_savings_n, 'id="daylight_savings_n"')?> <?=lang('no')?>
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

<?php $this->load->view('account/_account_footer');