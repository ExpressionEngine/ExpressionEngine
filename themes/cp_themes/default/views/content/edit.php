<?php extend_template('default')?>

<div id="filterMenu">
	<fieldset>
		<legend><?=lang('search_entries')?></legend>

	<?=form_open($search_form, array('name' => 'filterform', 'id' => 'filterform'))?>

		<div class="group">
			<?=form_dropdown('channel_id', $channel_select_options, $channel_selected, 'id="f_channel_id"').NBS.NBS?>
			<?=form_dropdown('cat_id', $category_select_options, $category_selected, 'id="f_cat_id"').NBS.NBS?>
			<?=form_dropdown('status', $status_select_options, $status_selected, 'id="f_status"').NBS.NBS?>
			<?=form_dropdown('date_range', $date_select_options, $date_selected, 'id="date_range"').NBS.NBS?>
			<?php
				// JS required theme, so ordering handled by table sorter
				//form_dropdown('order', $order_select_options, $order_selected, 'id="f_select_options"').NBS.NBS
			?>
			<?=form_dropdown('perpage', $perpage_select_options, $perpage_selected, 'id="f_perpage"')?>
		</div>

		<div id="custom_date_picker" style="display: none; margin: 0 auto 50px auto;width: 500px; height: 235px; padding: 5px 15px 5px 15px;border: 1px solid black;  background: #FFF;">
			<div id="cal1" style="width:250px; float:left; text-align:center;">
				<p style="text-align:left; margin-bottom:5px"><?=lang('start_date', 'custom_date_start')?>:&nbsp; <input type="text" name="custom_date_start" id="custom_date_start" value="yyyy-mm-dd" size="12" tabindex="1" /></p>
				<span id="custom_date_start_span"></span>
			</div>
			<div id="cal2" style="width:250px; float:left; text-align:center;">
				<p style="text-align:left; margin-bottom:5px"><?=lang('end_date', 'custom_date_end')?>:&nbsp; <input type="text" name="custom_date_end" id="custom_date_end" value="yyyy-mm-dd" size="12" tabindex="2" /></p>
				<span id="custom_date_end_span"></span>          
			</div>
        </div>

		<div>
			<label for="keywords" class="js_hide"><?=lang('keywords')?> </label><?=form_input($keywords, NULL,  'class="field shun" placeholder="'.lang('keywords').'"')?><br />
			<?=form_checkbox('exact_match', 'yes', $exact_match, 'id="exact_match"')?> <?=lang('exact_match', 'exact_match').NBS.NBS?>
			<?=form_dropdown('search_in', $search_in_options, $search_in_selected, 'id="f_search_in"').NBS.NBS?>
			<?=form_submit('submit', lang('search'), 'class="submit" id="search_button"').NBS.NBS?>
			<img src="<?=$cp_theme_url?>images/indicator.gif" class="searchIndicator" alt="Edit Search Indicator" style="margin-bottom: -5px; visibility: hidden;" width="16" height="16" />
			
			
			<?php if ($autosave_show):?>
				<a href="<?=BASE.AMP.'C=content_edit'.AMP.'M=autosaved'?>" class="submit submit_alt" id="autosaved_entries">
					<img src="<?=$cp_theme_url?>images/save_layout.png" width="12" height="14" alt="<?=lang('autosaved_entries')?>">
					<?=lang('autosaved_entries')?> <span class="notice"><?=required()?></span>
				</a>
			<?php endif;?>
			
		</div>

	<?=form_close()?>
	
	</fieldset>
</div> <!-- filterMenu -->
	
<?=form_open($entries_form, array('id' => 'entries_form'), $form_hidden)?>
	<?=$pagination_html?>
	
	<?=$table_html?>

	<div class="tableSubmit">
		<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
		<?php if (count($action_options) > 0):?>
		<?=form_dropdown('action', $action_options).NBS.NBS?>
		<?php endif;?>
	</div>

	<?=$pagination_html?>

<?=form_close()?>