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

		<div class="heading">
			<h2 class="edit">
			<span id="filter_ajax_indicator" style="visibility:hidden; float:right;"><img src="<?=$cp_theme_url?>images/indicator2.gif" style="padding-right:20px;" /></span>
			<?=lang('view_search_members')?></h2>
		</div>
		
		<div class="pageContents">

			<?php $this->load->view('_shared/message');?>

	        	<?=form_open('C=members'.AMP.'M=view_all_members', array('id' => 'member_form'))?>
					<div id="filterMenu">
						<fieldset>
							<legend><?=lang('total_members')?> <?=$total_members?></legend>

							<p>
								<?=form_label(lang('keywords'), 'member_name', 'class="field"')?>&nbsp;
								<?=form_input(array('id'=>'member_name', 'name'=>'member_name', 'class'=>'field', 'value'=>$member_name))?> 
							</p>
							<p>
								<?=form_label(lang('member_group'), 'group_id')?>&nbsp;
								<?=form_dropdown('group_id', $member_groups_dropdown, $selected_group, 'id="group_id"')?> 
					
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

								<?=form_label(lang('filter_by'), 'column_filter')?>&nbsp;
								<?=form_dropdown('column_filter', $column_filter_options, $column_filter_selected, 'id="column_filter"')?> 
					
								&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;


								<?=form_submit('submit', lang('search'), 'id="filter_member_submit" class="submit"')?>
							</p>
						</fieldset>
					</div>
	            <?=form_close()?>
	
<?php
	if ($member_list != FALSE):
	
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		lang('username'), 
		lang('screen_name'), 
		lang('email_address'), 
		lang('join_date'), 
		lang('last_visit'), 
		lang('member_group'), 
		form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"')
	);

		foreach ($member_list->result() as $member)
		{
			$this->table->add_row(					
									array('class' => 'username', 'data' => '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='. $member->member_id .'">'.$member->username.'</a>'),
									array('class' => 'screen_name', 'data' => $member->screen_name),
									'<a href="mailto:'.$member->email.'">'.$member->email.'</a>',
									// localized date
									$this->localize->convert_timestamp('%Y', $member->join_date).'-'.
									$this->localize->convert_timestamp('%m', $member->join_date).'-'.
									$this->localize->convert_timestamp('%d', $member->join_date),
									($member->last_visit == 0) ? ' - ' : $this->localize->set_human_time($member->last_visit),
									array('class' => 'group_'.$member->group_id, 'data' => $member_groups_dropdown[$member->group_id]),
									'<input class="toggle" type="checkbox" name="toggle[]" value="'.$member->member_id.'" />'
								);					
		}		
		
	?>
		

		
		<?=form_open('C=members'.AMP.'M=member_confirm')?>

		<?=$this->table->generate()?>


			<div class="tableSubmit">
 			<?php
			if (count($member_action_options) > 0):?>
				<?=form_dropdown('action', $member_action_options).NBS.NBS?>
 			<?php endif;?>

				<?=form_submit('effect_members', $delete_button_label, 'class="submit"'); ?>
			</div>		
			
			<span class="js_hide"><?=$pagination?></span>
			<span class="pagination" id="filter_pagination"></span>

			<?=form_close()?>

	<?php else:?>
		<p class="notice"><?=lang('no_members_matching_that_criteria')?></p>
	<?php endif;?>


	</div> <!-- pageContents -->
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_members.php */
/* Location: ./themes/cp_themes/default/members/view_members.php */