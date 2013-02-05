<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}

    $table['row_start'] = '<tr class="even">';
    $table['row_alt_start'] ='<tr class="odd">';

    $form_attributes = array(
                            'title'     => lang('view_search_members'),
                            'class'     => 'panel',
                            'selected'  => 'true',
                            'id'        => strtolower(str_replace(' ', '_', lang('view_search_members')))
                            );
    
    echo form_open('C=members'.AMP.'M=view_all_members', $form_attributes);?>
<div id="view_members" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	
				<?php $this->load->view('_shared/message');?>

		        	<?=form_open('C=members'.AMP.'M=view_all_members', array('id' => 'member_form'))?>

					<ul class="rounded">
						<li><?=lang('total_members').' '.$total_members?></li>
					</ul>
					
					<ul class="rounded">
						<li><?=form_input(array(
										'id'			=> 'member_name',
										'name'			=> 'member_name',
										'value'			=> $member_name,
										'placeholder'	=> lang('filter')))?></li>
						<li><?=form_label(lang('member_group_assignment'), 'group_id')?>
							<?=form_dropdown('group_id', 
											 $member_groups_dropdown, 
											 $selected_group, 
											 'id="group_id"')?></li>
					</ul>
					<?=form_submit('submit', lang('search'), 'id="filter_member_submit" class="whiteButton"')?>
		            <?=form_close()?>

	<?php
		if ($member_list != FALSE):

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
										$this->localize->formatted_date('%Y-%m-%d', $member->join_date),
										($member->last_visit == 0) ? ' - ' : $this->localize->human_time($member->last_visit),
									array('class' => 'group_'.$member->group_id, 'data' => $member_groups_dropdown[$member->group_id]),
										'<input class="toggle" type="checkbox" name="toggle[]" value="'.$member->member_id.'" />'
									);					
			}		

		?>



			<?=form_open('C=members'.AMP.'M=member_confirm')?>

			<?=$this->table->generate()?>

	 			<?php
				if (count($member_action_options) > 0):?>
					<?=form_dropdown('action', $member_action_options).NBS.NBS?>
	 			<?php endif;?>

					<?=form_submit('effect_members', $delete_button_label, 'class="whiteButton"'); ?>

				<?=form_close()?>

		<?php else:?>
			<div class="container"><?=lang('no_members_matching_that_criteria')?></div>
		<?php endif;?>	
	
	
	

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_members.php */
/* Location: ./themes/cp_themes/default/members/view_members.php */