<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=content_publish" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

	<?=form_open($search_form, array('name'=>'filterform', 'id'=>'filterform'), $search_form_hidden)?>

	<ul>
		<li><?=form_dropdown('channel_id', $channel_select_options, $channel_selected)?></li>
		<li><?=form_dropdown('cat_id', $category_select_options, $category_selected)?></li>
		<li><?=form_dropdown('status', $status_select_options, $status_selected)?></li>
		<li><?=form_dropdown('date_range', $date_select_options, $date_selected, 'id="date_range"')?></li>
		<li><?=form_dropdown('order', $order_select_options, $order_selected)?></li>
		<li><?=form_dropdown('perpage', $perpage_select_options, $perpage_selected)?></li>
	</ul>

	<div id="custom_date_picker" style="display: none; margin: 0 auto 50px auto;width: 385px; height: 185px; padding: 5px 15px 5px 15px;border: 1px solid black;  background: #FFF;">
		<div id="cal1" style="width:200px; float:left; text-align:center;">
			<p><?=lang('start_date', 'custom_date_start')?>:&nbsp; <input type="text" name="custom_date_start" id="custom_date_start" value="yyyy-mm-dd" size="12" tabindex="1" /></p>
			<span id="custom_date_start_span"></span>
		</div>
		<div id="cal2" style="text-align:center;">
			<p><?=lang('end_date', 'custom_date_end')?>:&nbsp; <input type="text" name="custom_date_end" id="custom_date_end" value="yyyy-mm-dd" size="12" tabindex="2" /></p>
			<span id="custom_date_end_span"></span>          
		</div>
	</div>

	<ul>
		<li><?=lang('keywords', 'keywords')?> <?=form_input($keywords)?></li>
		<li><?=form_checkbox('exact_match', 'yes', $exact_match, 'id="exact_match"')?> <?=lang('exact_match', 'exact_match')?></li>
		<li><?=form_dropdown('search_in', $search_in_options, $search_in_selected)?></li>
	</ul>

<?=form_submit('submit', lang('search'), 'class="whiteButton" id="search_button"')?>

	<?=form_close()?>

	<?php if ($total_count == 0):?>
		<div class="tableFooter">
			<p class="notice"><?=lang('no_entries_matching_that_criteria')?></p>
		</div>
	<?php else:?>

	<?=form_open($entries_form, array('id' => 'entries_form'), $form_hidden)?>
	<?php foreach ($entries as $entry):?>
		<?php
			$channel = preg_replace('/<div class=\'smallNoWrap\' >(.*)<\/div>/i', "$1", $entry[6]);
			$entry[8] = str_replace('class="toggle" ', '', $entry[8]);
		?>
		
		
		<div class="label">
			<p><strong><?=$table_headings[3]?>:</strong> <?=$entry[3]?></p>
			<p><strong><?=$table_headings[6]?>:</strong> <?=$channel?></p>
			<p><strong><?=$table_headings[7]?>:</strong> <?=$entry[7]?></p>
			<p><strong><?=$table_headings[5]?>:</strong> <?=$entry[5]?></p>
		</div>
		<ul>
			<li><?=$entry[1]?></li>
			<li><?=$entry[4]?></li>
			<li><?=$entry[5]?></li>
			<li><?=$entry[8]?></li>
		</ul>

	<?php endforeach;?>


<?php if ($autosave_show):?>
<p class="notice"><?=required()?><?=lang('autosave_data_available')?></p>
<?php endif;?>

<div class="tableSubmit">
<?=form_submit('submit', lang('submit'), 'class="whiteButton"')?>
<?php if (count($action_options) > 0):?>
	<ul>
		<li><?=form_dropdown('action', $action_options)?></li>
	</ul>
<?php endif;?>
</div>

<?=$pagination?>

<?php endif; /* if $total_count > 0*/?>

<?=form_close()?>


</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file edit.php */
/* Location: ./themes/cp_themes/mobile/content/edit.php */