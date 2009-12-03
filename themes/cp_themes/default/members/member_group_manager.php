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

        <div class="heading"><h2><?=lang('member_groups')?></h2></div>

		<div class="pageContents">
			<?php $this->load->view('_shared/message');?>
			<div class="clear_left"></div>
		<?php
			$this->table->set_template(array('table_open' => '<table class="mainTable clear_left" cellspacing="0" cellpadding="0">'));
			$this->table->set_heading(
										lang('group_title'),
										lang('edit_group'),
										lang('security_lock'),
										lang('group_id'),
										lang('members'),
										lang('delete')
									  );

			foreach($groups as $group)
			{
				$this->table->add_row(
										($group['can_access_cp'] == 'y') ? '<span class="notice">* '.$group['title'].'</span>' : $group['title'],
										'<a href="'.BASE.AMP.'C=members'.AMP.'M=edit_member_group'.AMP.'group_id='.$group['group_id'].'">'.lang('edit_group').'</a>',
										$group['security_lock'],
										$group['group_id'],
										'('.$group['member_count'].') <a href="'.BASE.AMP.'C=members'.AMP.'M=view_all_members'.AMP.'group_id='.$group['group_id'].'">'.lang('view').'</a>',
										($group['delete']) ? '<a href="'.BASE.AMP.'C=members'.AMP.'M=delete_member_group_conf'.AMP.'group_id='.$group['group_id'].'">'.lang('delete').'</a>' : '--'
									  );
			}
			
			echo $this->table->generate();
		?>
			<p><strong class="notice">* <?=lang('member_has_cp_access')?></strong></p>

			<?=$paginate?>
		
			<?=form_open('C=members'.AMP.'M=edit_member_group')?>
		
			<p><?=lang('create_group_based_on_old', 'clone_id')?> <?=form_dropdown('clone_id', $clone_group_options)?></p>
		
			<p><?=form_submit('submit', lang('submit'), 'class="submit"')?>
		
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

/* End of file groups.php */
/* Location: ./themes/cp_themes/default/members/groups.php */