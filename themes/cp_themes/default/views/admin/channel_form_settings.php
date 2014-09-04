<?php extend_template('default') ?>

<?=form_open('C=admin_content'.AMP.'M=update_channel_form_settings', 'id="channel-form-settings"')?>

	<?php
	$this->table->set_heading(
		lang('channel'),
		lang('channel_form_default_status'),
		lang('channel_form_allow_guest_posts'),
		lang('channel_form_guest_captcha'),
		lang('channel_form_guest_author')
	);
	
	if (count($channels) > 0)
	{
		foreach ($channels as $id => $settings)
		{
			$this->table->add_row(
				$settings['title'],
				form_dropdown('default_status['.$id.']', $settings['statuses'], $settings['default_status']),

				form_yes_no_toggle('allow_guest_posts['.$id.']', $settings['allow_guest_posts']),
				form_yes_no_toggle('require_captcha['.$id.']', $settings['require_captcha']),

				form_dropdown('default_author['.$id.']', $settings['authors'], $settings['default_author'])
			);
		}
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_channels_exist'), 'colspan' => 6));
	}
	
	echo $this->table->generate();
	?>

	<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

<?=form_close()?>