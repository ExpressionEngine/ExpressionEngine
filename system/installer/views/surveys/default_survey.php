<div class="shade">

<h2><?=lang('opt_in_survey')?></h2>

<p><?=lang('help_with_survey')?></p>


<form action="<?=$action_url?>" method="post" accept-charset="utf-8">

	<div>
	<p><?=lang('participate_in_survey', 'participate_in_survey')?></p>
	<?=form_radio('participate_in_survey', 'y', TRUE, 'id="participate_in_survey_y" onclick="document.getElementById(\'survey_body\').style.display=\'block\'"')?>
		<label for="participate_in_survey_y"><?=lang('yes')?></label> &nbsp;&nbsp;&nbsp;&nbsp;
	<?=form_radio('participate_in_survey', 'n', FALSE, 'id="participate_in_survey_n" onclick="document.getElementById(\'survey_body\').style.display=\'none\'"')?> <label for="participate_in_survey_n"><?=lang('no')?></label>
	</div>

	<div id="survey_body">
		<hr />

		<div class="pad">
			<p><?=lang('send_anonymous_server_data', 'send_anonymous_server_data')?></p>
			<?=form_radio('send_anonymous_server_data', 'y', TRUE, 'id="send_anonymous_server_data_y"')?>
				<label for="send_anonymous_server_data_y"><?=lang('yes')?></label> &nbsp;&nbsp;&nbsp;&nbsp;
			<?=form_radio('send_anonymous_server_data', 'n', FALSE, 'id="send_anonymous_server_data_n"')?> <label for="send_anonymous_server_data_n"><?=lang('no')?></label>
			<span style="font-size:smaller"><?=lang('what_server_data_is_sent')?>
				<a href="#" onclick="toggle_server_data();return false;"><?=lang('show_hide_to_see_server_data')?></a>
			</span>

			<div id="server_data" style="display:none;font-size:smaller;">
				<dl>
			<?php foreach ($anonymous_server_data as $key => $val):?>
				<dt><?=$key?></dt>
				<dd><?=$val?></dd>
			<?php endforeach; ?>
				</dl>
			</div>
		</div>

	</div>

	<p>
		<?=form_submit('submit', lang('submit'), 'class="submit"')?>
	</p>

</form>

</div>
<script type="text/javascript" charset="utf-8">
	function toggle_server_data() {
		if (document.getElementById('server_data').style.display == 'block') {
			document.getElementById('server_data').style.display='none';
		} else {
			document.getElementById('server_data').style.display='block';
		}
	}
</script>