
<?=form_open('C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=comment', 'id="comment_filter"')?>
	<fieldset class="shun">
		<legend><?=lang('filter_comments')?></legend>
		<div class="group">
			<label for="keywords" class="js_hide"><?=lang('keywords')?> </label><?=form_input('keywords', $keywords, 'class="field shun" placeholder="' . lang('keywords') . '"')?><br />

			<?=form_dropdown('channel_id', $channel_select_opts, $channel_selected, 'id="f_channel_id"') . NBS . NBS?>
			<?=form_dropdown('status', $status_select_opts, $status_selected, 'id="f_status"') . NBS . NBS?>
			<?=form_dropdown('date_range', $date_select_opts, $date_selected, 'id="date_range"') . NBS . NBS?>
			<?=form_submit('submit', lang('search'), 'class="submit" id="search_button"') . NBS . NBS?>
			<img src="<?=$cp_theme_url?>images/indicator.gif" class="searchIndicator" alt="Edit Search Indicator" style="margin-bottom: -5px; visibility: hidden;" width="16" height="16" />
		</div>
	</fieldset>
<?=form_close()?>


<?=form_open('C=addons_modules' . AMP . 'M=show_module_cp' . AMP . 'module=comment' . AMP . 'method=modify_comments', array('name' => 'target', 'id' => 'target'))?>
<?php
echo $table_html;
echo $pagination_html;
?>

<div class="tableSubmit">
	<?=form_submit('submit', lang('submit'), 'class="submit"') . NBS . NBS?>
	<?=form_dropdown('action', $form_options, '', 'id="comment_action"') . NBS . NBS?>
</div>
<?=form_close()?>