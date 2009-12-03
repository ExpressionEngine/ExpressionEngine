<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=category_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>


	<?=form_open('C=admin_content'.AMP.'M=update_custom_category_fields', '', $form_hidden)?>

	<div class="label">
		<?=required().lang('field_name', 'field_name')?><br />
		<?=lang('field_name_cont')?>			
	</div>
	<ul>
		<li><?=form_input(array('id'=>'field_name','name'=>'field_name','class'=>'field','value'=>$field_name))?></li>
	</ul>

	<div class="label">	
		<?=required().lang('field_label', 'field_label')?><br />
		<?=lang('cat_field_label_info')?>
	</div>
	<ul>
		<li><?=form_input(array('id'=>'field_label','name'=>'field_label','class'=>'field','value'=>$field_label))?></li>
	</ul>

	<div class="label">
		<?=lang('field_type', 'field_type')?>
	</div>
	<ul>
		<li><?=form_dropdown('field_type', $field_type_options, $field_type, 'id="field_type"')?></li>
		<li><?=lang('field_max_length', 'field_max1')?> <?=form_input(array('id'=>'field_maxl','name'=>'field_maxl', 'size'=>4,'value'=>$field_maxl))?></li>
		<li><?=lang('textarea_rows', 'field_ta_rows')?> <?=form_input(array('id'=>'field_ta_rows','name'=>'field_ta_rows', 'size'=>4,'value'=>$field_ta_rows))?></li>
	</ul>
	
	<div class="label">
		<?=lang('field_list_items', 'field_list_items')?><br /> 
		<?=lang('field_list_instructions')?><br /> 
	</div>
	<ul>
		<li><?=form_textarea(array('id'=>'field_list_items','name'=>'field_list_items', 'rows'=>10, 'cols'=>50, 'value'=>$field_list_items))?></li>
	</ul>

	<div class="label">
		<?=form_label(lang('deft_field_formatting'), 'field_default_fmt')?>
	</div>
	<ul>
		<li><?=form_dropdown('field_default_fmt', $field_default_fmt_options, $field_default_fmt, 'id="field_default_fmt"')?></li>
	</ul>

	<?php if ($update_formatting):?>
		<div class="label">
			<p class="notice" style="display: none;" id="formatting_notice_info"><?=lang('fmt_has_changed')?></p>
				<?=form_checkbox('update_formatting', 'y', FALSE, 'id="update_formatting"')?>
			</p>
		</div>
		<ul>
			<li><?=lang('update_existing_cat_fields', 'update_formatting')?></li>
		</ul>
	<?php endif;?>

	<div class="label">
		<?=lang('show_formatting_buttons', 'show_formatting_buttons')?>
	</div>
	<ul>
		<li><?=form_radio('field_show_fmt', 'y', $field_show_fmt_y, 'id="field_show_fmt_y"')?>
		<?=lang('yes', 'field_show_fmt_y')?><br />
		<?=form_radio('field_show_fmt', 'n', $field_show_fmt_n, 'id="field_show_fmt_n"')?>
		<?=lang('no', 'field_show_fmt_n')?></li>
	</ul>

	<div class="label">
		<?=lang('text_direction', 'text_direction')?>
	</div>
	<ul>
		<li><?=form_radio('field_text_direction', 'ltr', $field_text_direction_ltr, 'id="field_text_direction_ltr"')?>
		<?=lang('ltr', 'field_text_direction_ltr')?><br />
		<?=form_radio('field_text_direction', 'rtl', $field_text_direction_rtl, 'id="field_text_direction_rtl"')?>
		<?=lang('rtl', 'field_text_direction_rtl')?></li>
	</ul>

	<div class="label">
		<?=lang('is_field_required', 'is_field_required')?>
	</div>
	<ul>
		<li><?=form_radio('field_required', 'y', $field_required_y, 'id="field_required_y"')?>
		<?=lang('yes', 'field_required_y')?><br />
		<?=form_radio('field_required', 'n', $field_required_n, 'id="field_required_n"')?>
		<?=lang('no', 'field_required_n')?></li>
	</ul>

	<div class="label">
		<?=lang('field_order', 'field_order')?>
	</div>
	<ul>
		<li><?=form_input(array('id'=>'field_order','name'=>'field_order', 'size'=>4,'value'=>$field_order))?></li>
	</ul>

	<?=form_submit('custom_field_edit', lang($submit_lang_key), 'class="whiteButton"')?>

	<?=form_close()?>



</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_custo_category_field.php */
/* Location: ./themes/cp_themes/default/admin/edit_custo_category_field.php */