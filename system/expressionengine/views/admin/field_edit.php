<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

		<?=form_open('C=admin_content'.AMP.'M=field_update', '', $form_hidden)?>

		<table class="mainTable padTable" cellspacing="0" cellpadding="0" border="0">
		<thead>
			<tr>
				<th colspan="2">
					<?=lang('field_settings')?>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td width="40%">
					<strong><?=lang('field_type')?></strong> 
				</td>
				<td>
					<?=form_dropdown('field_type', $field_type_options, $field_type, 'id="field_type"')?>
				</td>
			</tr>
			<tr>
				<td>
					<?=required().form_label(lang('field_label'), 'field_label')?><br /><?=lang('field_label_info')?>
				</td>
				<td>
					<?=form_input(array('id'=>'field_label','name'=>'field_label','class'=>'fullfield','value'=>$field_label))?>
				</td>
			</tr>
			<tr>
				<td>
					<?=required().form_label(lang('field_name'), 'field_name')?><br /><?=lang('field_name_cont')?>
				</td>
				<td>
					<?=form_input(array('id'=>'field_name','name'=>'field_name','class'=>'fullfield','value'=>$field_name))?>
				</td>
			</tr>
			<tr>
				<td>
					<?=form_label(lang('field_instructions'), 'field_instructions')?><br />
					<?=lang('field_instructions_info')?>
				</td>
				<td>
					<?=form_textarea(array('id'=>'field_instructions','name'=>'field_instructions','class'=>'fullfield','value'=>$field_instructions))?>
				</td>
			</tr>
			<tr>
				<td>
					<strong><?=lang('is_field_required')?></strong>
				</td>
				<td>
					<?=form_radio('field_required', 'y', ($field_required == 'y'), 'id="field_required_y"')?>
					<?=lang('yes', 'field_required_y')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<?=form_radio('field_required', 'n', ($field_required == 'n'), 'id="field_required_n"')?>
					<?=lang('no', 'field_required_n')?>
				</td>
			</tr>
			<tr>
				<td>
					<strong><?=lang('is_field_searchable')?></strong>
				</td>
				<td>
					<?=form_radio('field_search', 'y', ($field_search == 'y'), 'id="field_search_y"')?>
					<?=lang('yes', 'field_search_y')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<?=form_radio('field_search', 'n', ($field_search == 'n'), 'id="field_search_n"')?>
					<?=lang('no', 'field_search_n')?>
				</td>
			</tr>
			<tr>
				<td>
					<strong><?=lang('field_is_hidden')?></strong><br />
					<?=lang('hidden_field_blurb')?>
				</td>
				<td>
					<?=form_radio('field_is_hidden', 'n', ($field_is_hidden == 'n'), 'id="field_is_hidden_n"')?>
					<?=lang('yes', 'field_is_hidden_y')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<?=form_radio('field_is_hidden', 'y', ($field_is_hidden == 'y'), 'id="field_is_hidden_y"')?>
					<?=lang('no', 'field_is_hidden_n')?>
				</td>
			</tr>
			<tr>
				<td>
					<?=lang('field_order', 'field_order')?>
				</td>
				<td>
					<?=form_input(array('id'=>'field_order','name'=>'field_order', 'size'=>4,'value'=>$field_order))?>
				</td>
			</tr>
		</tbody>
		</table>

		<?php $this->table->set_template($cp_table_template);

		foreach($field_type_tables as $ft => $data):?>

			<div id="ft_<?=$ft?>" class="js_hide">
			
			<?php
				if (is_array($data))
				{
					$this->table->rows = $data;
					$this->table->set_heading(
						array('width' => '40%', /*'colspan' => 2, */'data' => lang('field_type_options')/*.' :: '.$field_type_options[$ft]*/),
						''
					);

					$data = $this->table->generate();
					$this->table->clear();
				}
				echo $data;
			?>

			</div>
		
		<?php endforeach;?>

		<p><?=form_submit('field_edit_submit', lang($submit_lang_key), 'class="submit"')?></p>
		
		<?=form_close()?>
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file field_edit.php */
/* Location: ./themes/cp_themes/default/admin/field_edit.php */