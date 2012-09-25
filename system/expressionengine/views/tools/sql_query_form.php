<?php extend_template('default') ?>

<p><?=lang('sql_query_instructions')?></p>
<p><strong><?=lang('advanced_users_only')?></strong></p>
<?=form_open('C=tools_data'.AMP.'M=sql_run_query')?>
	<div><?=form_textarea(array('name' => 'thequery', 'id' => 'thequery', 'rows' => 10, 'style' => "width:100%", 'class' => 'shun'))?></div>
	<div>
		<?=form_checkbox(array('name' => 'debug', 'id' => 'debug', 'value' => 'y', 'class' => 'shun'))?>
		<?=lang('sql_query_debug', 'debug')?>
	</div>
	<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
<?=form_close()?>