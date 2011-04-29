<div class="shade">

<h2><?=lang('opt_in_survey')?></h2>

<p><?=lang('help_with_survey')?></p>


<form action="<?=$action_url?>" method="post" accept-charset="utf-8">	
	
	<div>
	<?=lang('participate_in_survey', 'participate_in_survey')?><br />
	<?=form_radio('participate_in_survey', 'y', TRUE, 'id="participate_in_survey_y" onclick="document.getElementById(\'survey_body\').style.display=\'block\'"')?>
		<?=lang('yes')?> &nbsp;&nbsp;&nbsp;&nbsp;
	<?=form_radio('participate_in_survey', 'n', FALSE, 'id="participate_in_survey_n" onclick="document.getElementById(\'survey_body\').style.display=\'none\'"')?> <?=lang('no')?>
	</div>
	
	<div id="survey_body">
		<hr />
		<div class="pad">
			<?=lang('send_anonymous_server_data', 'send_anonymous_server_data')?><br />
			<?=form_radio('send_anonymous_server_data', 'y', TRUE, 'id="send_anonymous_server_data_y"')?>
				<?=lang('yes')?> &nbsp;&nbsp;&nbsp;&nbsp;
			<?=form_radio('send_anonymous_server_data', 'n', FALSE, 'id="send_anonymous_server_data_n""')?> <?=lang('no')?>
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
		
		<div class="pad">
			<?=lang('would_you_recommend', 'would_you_recommend')?><br />
			<?=lang('unlikely')?>&nbsp;&nbsp;&nbsp;&nbsp;
			<?php for ($i = 0; $i <= 10; $i ++):?>
				<?=form_radio('would_you_recommend', $i, FALSE, 'id="would_you_recommend_'.$i.'"')?> <?=$i?> &nbsp;&nbsp;
			<?php endfor;?>
			&nbsp;&nbsp;<?=lang('highly_likely')?>
		</div>
		
		<div class="pad">
			<?=lang('additional_comments', 'additional_comments')?><br />
			<?=form_textarea('additional_comments')?>
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