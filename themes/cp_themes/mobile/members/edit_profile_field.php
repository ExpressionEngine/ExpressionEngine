<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members<?=AMP?>M=custom_profile_fields" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

			<?=form_open('C=members'.AMP.'M=update_profile_fields'.AMP.'U=1', '', $hidden_form_fields);
			$notice = '<span class="notice">*</span> ';
			?>
			<div class="label">
			<?=$notice.form_label(lang('fieldname'), 'm_field_name').'<br />'.lang('fieldname_cont').form_error('m_field_name')?>
			</div>
			<ul class="rounded">
				<li><?=form_input('m_field_name', set_value('m_field_name', $m_field_name), 'class="field"')?></li>
			</ul>
			<div class="label">
			<?=$notice.form_label(lang('fieldlabel'), 'm_field_label').'<br />'.lang('for_profile_page').form_error('m_field_label')?>
			</div>
			<ul class="rounded">
				<li><?=form_input('m_field_label', set_value('m_field_label', $m_field_label), 'class="field"')?></li>
			</ul>

			<div class="label">
			<?=form_label(lang('field_description'), 'm_field_description').'<br />'.lang('field_description_info')?>
			</div>
			<ul class="rounded">
				<li><?=form_input('m_field_description', set_value('m_field_description', $m_field_description), 'class="field" id="m_field_description"')?></li>
			</ul>

			<div class="label">			
			<?=form_label(lang('field_order'), 'm_field_order')?>
			</div>
			<ul class="rounded">
				<li><?=form_input('m_field_order', set_value('m_field_order', $m_field_order), 'class="field" id="m_field_order"')?></li>
			</ul>

			<div class="label">			
			<?=form_label(lang('field_width'), 'm_field_width')?>
			</div>
			<ul class="rounded">
				<li><?=form_input('m_field_width', set_value('m_field_width', $m_field_width), 'class="field" id="m_field_width"')?></li>
			</ul>

			<div class="label">
			<?=form_label(lang('field_type'), 'm_field_type')?>
			</div>
			<ul class="rounded">
				<li><?=form_dropdown('m_field_type', $m_field_type_options, set_value('m_field_type', $m_field_type), "onchange='showhide_element(this.options[this.selectedIndex].value);'")?></li>
				<li><span id="select_block" style="display: <?=$select_js?>">
					<?=form_label(lang('pull_down_items'), 'm_field_list_items')?><br />
					<?=form_textarea(array(
	                       'id'    => 'm_field_list_items',
	                       'name'  => 'm_field_list_items',
	                       'cols'  => 90,
	                       'rows'  => 10,
	                       'class' =>
	                       'field',
	                       'value' => set_value('m_field_list_items', $m_field_list_items)))?></span>
					<span id="text_block" style="display: <?=$text_js?>">
					<?=lang('m_max_length', 'm_field_maxl')?><br />
					<?=form_input(array(
                        'id'    => 'm_field_ta_rows',
                        'name'  => 'm_field_ta_rows',
                        'class' => 'field',
                        'value' => set_value('m_field_ta_rows', $m_field_ta_rows)))?>
					</span>
					<span id="textarea_block" style="display: <?=$textarea_js?>">
					<?=lang('text_area_rows', 'm_field_ta_rows')?><br />
					<?=form_input(array(
                        'id'    => 'm_field_ta_rows',
                        'name'  => 'm_field_ta_rows',
                        'class' => 'field',
                        'value' => set_value('m_field_ta_rows', $m_field_ta_rows)))?>
					</span>
				</li>
			</ul>

			<div class="label">
			<?=lang('field_format', 'm_field_fmt')?><br />
			<?=lang('text_area_rows_cont')?>
			</div>
			<ul class="rounded">
				<li><?=form_dropdown('m_field_fmt', $m_field_fmt_options, set_value('m_field_fmt', $m_field_fmt))?></li>
			</ul>

			<div class="label">
			<?=lang('is_field_required', 'm_field_required')?>
			</div>
			<ul class="rounded">
				<li><?=form_dropdown('m_field_required', $m_field_required_options, set_value('m_field_required', $m_field_required_yes))?></li>
			</ul>

			<div class="label">
			<?=lang('is_field_public', 'm_field_reg')?><br />
			<?=lang('is_field_public_cont')?>
			</div>
			<ul class="rounded">
				<li><?=form_dropdown('m_field_public', $m_field_public_options, set_value('m_field_public', $m_field_public_yes))?></li>
			</ul>

			<div class="label">
			<?=lang('is_field_reg', 'm_field_reg')?><br />
			<?=lang('is_field_public_cont')?>
			</div>
			<ul class="rounded">
				<li><?=form_dropdown('m_field_reg', $m_field_reg_options, set_value('m_field_reg', $m_field_reg_yes))?></li>
			</ul>

			<div class="container pad">* <?=lang('required_fields')?></div>

			<p><?=form_submit('', $submit_label, 'class="whiteButton"')?></p>

			<?=form_close()?>


</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_profile_field.php */
/* Location: ./themes/cp_themes/mobile/members/edit_profile_field.php */