<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=field_group_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>


		<?=form_open('C=admin_content'.AMP.'M=field_update', '', $form_hidden)?>

		<div class="label">
			<?=required().form_label(lang('field_label'), 'field_label')?><br /><?=lang('field_label_info')?>
		</div>
		<ul>
			<li><?=form_input(array('id'=>'field_label','name'=>'field_label','class'=>'fullfield','value'=>$field_label))?></li>
		</ul>

		<div class="label">
			<?=required().form_label(lang('field_name'), 'field_name')?><br /><?=lang('field_name_cont')?>
		</div>
		<ul>
			<li><?=form_input(array('id'=>'field_name','name'=>'field_name','class'=>'fullfield','value'=>$field_name))?></li>
		</ul>

		<div class="label">
			<?=form_label(lang('field_instructions'), 'field_instructions')?><br /><?=lang('field_instructions_info')?>
		</div>
		<ul>
		<li><?=form_textarea(array('id'=>'field_instructions','name'=>'field_instructions','class'=>'fullfield','value'=>$field_instructions))?></li>
</ul>

		<div class="label">
			<strong><?=lang('field_type', 'field_type')?></strong> 
		</div>
		<ul>
			<li><?=form_dropdown('field_type', $field_type_options, $field_type, 'id="field_type"')?></li>
			<li><?=form_radio('field_pre_populate', 'n', $field_pre_populate_n, 'id="field_pre_populate_n"')?>
			<?=lang('field_populate_manually', 'field_pre_populate_n')?><br />
			<?=form_radio('field_pre_populate', 'y', $field_pre_populate_y, 'id="field_pre_populate_y"')?>
			<?=lang('field_populate_from_channel', 'field_pre_populate_y')?></li>
		</ul>

	<!--text input maxsize-->
	<div class="field_format_option text_format">
		<div class="label">
			<?=lang('field_max_length', 'field_max1')?> 
		</div>
		<ul>
			<li><?=form_input(array('id'=>'field_maxl','name'=>'field_maxl', 'size'=>4,'value'=>$field_maxl))?></li>
		</ul>
		<div class="label">
			<?=form_label(lang('field_content_text'), 'field_content_text')?>
		</div>
		<ul>
			<li><?=form_dropdown('field_content_text', $field_content_options_text, $field_content_text, 'id="field_content_text"')?></li>
		</ul>
	</div>
	
	<!--text area maxrows-->
	<div class="field_format_option textarea_format">
		<div class="label">
			<?=lang('textarea_rows', 'field_ta_rows')?> 
		</div>
		<ul>
			<li><?=form_input(array('id'=>'field_ta_rows','name'=>'field_ta_rows', 'size'=>4,'value'=>$field_ta_rows))?></li>
		</ul>
	</div>
	<!-- file -->
	<div class="field_format_option file_format">
		<div class="label">
			<?=form_label(lang('field_content_file'), 'field_content_file')?>
		</div>
		<ul>
			<li><?=form_dropdown('field_content_file', $field_content_options_file, $field_content_file, 'id="field_content_file"')?></li>
		</ul>
	</div>

	<!--select options-->
	<div class="field_format_option select_format_n">
		<div class="label">
			<label for="select_list_items" id="select_list_items">
			<?=lang('field_list_items')?>
			</label>
			<label for="multi_select_list_items" id="multi_select_list_items">
			<?=lang('multi_list_items')?>
			</label>
			<?=lang('field_list_instructions', 'field_list_instructions')?>
		</div>
		<ul>
			<li><?=form_textarea(array('id'=>'field_list_items','class'=>'fullfield','name'=>'field_list_items', 'rows'=>10, 'cols'=>50, 'value'=>$field_list_items))?></li>
		</ul>
	</div>

	<div class="field_format_option select_format_y">
		<div class="label">
			<?=lang('select_channel_for_field', 'field_pre_populate_id')?> 
		</div>
		<ul>
			<li><?=form_dropdown('field_pre_populate_id', $field_pre_populate_id_options, $field_pre_populate_id_select, 'id="field_pre_populate_id"')?></li>
		</ul>
	</div>

	<!--relationship-->
	<div class="field_format_option rel_format">
		<div class="label">
			<?=lang('select_related_channel', 'field_related_channel_id')?>
		</div>
		<ul><li><?=form_dropdown('field_related_channel_id', $field_related_channel_id_options, $field_related_id, 'id="field_related_channel_id"')?></li></ul>
	</div>
	<div class="field_format_option rel_format">
		<div class="label">
			<?=lang('display_criteria', 'display_criteria')?>
		</div>
		<ul><li>
		<?=form_dropdown('field_related_orderby', $field_related_orderby_options, $field_related_orderby, 'id="field_related_orderby"')?> 
		<?=lang('in')?> 
		<?=form_dropdown('field_related_sort', $field_related_sort_options, $field_related_sort, 'id="field_related_sort"')?> 
		<?=lang('limit')?> 
		<?=form_dropdown('field_related_max', $field_related_max_options, $field_related_max, 'id="field_related_max"')?></li></ul>
	</div>

	<div class="label">
		<?=form_label(lang('deft_field_formatting'), 'field_fmt')?>
	</div>
		<ul>
			<li><?=form_dropdown('field_fmt', $field_fmt_options, $field_fmt, 'id="field_fmt"')?></li>
			<li><?=$edit_format_link?></li>
			<li><strong><?=lang('show_formatting_buttons')?></strong></li>
			<li><?=form_radio('field_show_fmt', 'y', $field_show_fmt_y, 'id="field_show_fmt_y"')?>
				<?=lang('yes', 'field_show_fmt_y')?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?=form_radio('field_show_fmt', 'n', $field_show_fmt_n, 'id="field_show_fmt_n"')?>
				<?=lang('no', 'field_show_fmt_n')?>
			</li>
			<li id="formatting_unavailable"><?=lang('formatting_no_available')?></li>
		</ul>

		<div class="label">
			<?=lang('text_direction', 'text_direction')?>
		</div>
		<ul>
			<li id="direction_available">
				<?=form_radio('field_text_direction', 'ltr', $field_text_direction_ltr, 'id="field_text_direction_ltr"')?>
				<?=lang('ltr', 'field_text_direction_ltr')?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				<?=form_radio('field_text_direction', 'rtl', $field_text_direction_rtl, 'id="field_text_direction_rtl"')?>
				<?=lang('rtl', 'field_text_direction_rtl')?>
			</li>
			<li class="notice" id="direction_unavailable"><?=$this->lang->line('direction_unavailable')?></li>
		</ul>


		<div class="label">
			<?=lang('is_field_required', 'is_field_required')?>
		</div>
		<ul><li>
		<?=form_radio('field_required', 'y', $field_required_y, 'id="field_required_y"')?>
		<?=lang('yes', 'field_required_y')?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<?=form_radio('field_required', 'n', $field_required_n, 'id="field_required_n"')?>
		<?=lang('no', 'field_required_n')?></li>
		</ul>

		<div class="label">
			<?=lang('is_field_searchable', 'is_field_searchable')?>
		</div>
		<ul><li>
			<?=form_radio('field_search', 'y', $field_search_y, 'id="field_search_y"')?>
			<?=lang('yes', 'field_search_y')?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<?=form_radio('field_search', 'n', $field_search_n, 'id="field_search_n"')?>
			<?=lang('no', 'field_search_n')?></li>
		</ul>

		<div class="label">
			<?=lang('field_is_hidden', 'field_is_hidden')?> <br />
			<?=lang('hidden_field_blurb')?>
		</div>
		<ul><li>
			<?=form_radio('field_is_hidden', 'n', $field_is_hidden_y, 'id="field_is_hidden_y"')?>
			<?=lang('yes', 'field_is_hidden_y')?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<?=form_radio('field_is_hidden', 'y', $field_is_hidden_n, 'id="field_is_hidden_n"')?>
			<?=lang('no', 'field_is_hidden_n')?></li>
		</ul>

		<div class="label">
			<?=lang('field_order', 'field_order')?>
		</div>
		<ul>
			<li><?=form_input(array('id'=>'field_order','name'=>'field_order', 'size'=>4,'value'=>$field_order))?></li>
		</ul>

		<?php if ($field_id != ''):?>

			<div id="update_formatting_div">
				<div class="label"><?=lang('fmt_has_changed')?></div>
				<ul>
					<li><?=form_checkbox('update_formatting', 'y', FALSE, 'id="update_formatting"')?>
					<?=lang('update_existing_fields')?></li>
				</ul>
			</div>
		<?php endif;?>

		<p><?=form_submit('field_edit_submit', lang($submit_lang_key), 'class="whiteButton"')?></p>


		<?=form_close()?>


		


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file field_edit.php */
/* Location: ./themes/cp_themes/default/admin/field_edit.php */