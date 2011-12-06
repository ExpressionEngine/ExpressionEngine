
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment', 'id="comment_filter"')?>
	<fieldset class="shun">
		<legend><?=lang('filter_comments')?></legend>
		<div class="group">
			<?=form_dropdown('channel_id', $channel_select_opts, $channel_selected, 'id="f_channel_id"').NBS.NBS?>
			<?=form_dropdown('status', $status_select_opts, $status_selected, 'id="f_status"').NBS.NBS?>
			<?=form_dropdown('date_range', $date_select_opts, $date_selected, 'id="date_range"').NBS.NBS?>
			<?=form_submit('submit', lang('search'), 'class="submit" id="search_button"')?>
		</div>
	</fieldset>
<?=form_close()?>


<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=modify_comments', array('name' => 'target', 'id' => 'target'))?>
<?php
echo $table_html;
echo $pagination_html;
?>

<div class="tableSubmit">
	<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
	<?=form_dropdown('action', $form_options, '', 'id="comment_action"').NBS.NBS?>
</div>
<?=form_close()?>