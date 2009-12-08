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

		<div class="heading"><h2><?=lang('register_member')?></h2></div>
		<div class="pageContents">

			<?=form_open('C=members'.AMP.'M=new_member_form')?>
			<?php
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(
				array('data' => '&nbsp;', 'style' => 'width:50%;'),
				'&nbsp;'
			);

			// Username
			$this->table->add_row(array(
					form_label(lang('username'), 'username').NBS.form_error('username'),
					form_input(array(
						'id'	=> 'username',
						'name'	=> 'username',
						'class' => 'field',
						'value' => set_value('username')
						)
					)
				)
			);

			// Password
			$this->table->add_row(array(
					form_label(lang('password'), 'password').NBS.form_error('password'),
					form_password(array(
						'id'	=> 'password',
						'name'	=> 'password',
						'class' => 'field',
						'value' => set_value('password')
						)
					)
				)
			);

			// Password Confirm
			$this->table->add_row(array(
					form_label(lang('password_confirm'), 'password_confirm').NBS.form_error('password_confirm'),
					form_password(array(
						'id'	=> 'password_confirm',
						'name'	=> 'password_confirm',
						'class' => 'field',
						'value' => set_value('password_confirm')
						)
					)
				)
			);

			// Screen Name
			$this->table->add_row(array(
					form_label(lang('screen_name'), 'screen_name').NBS.form_error('screen_name'),
					form_input(array(
						'id'	=> 'screen_name',
						'name'	=> 'screen_name',
						'class' => 'field',
						'value' => set_value('screen_name')
						)
					)
				)
			);

			// Email
			$this->table->add_row(array(
					form_label(lang('email'), 'email').NBS.form_error('email'),
					form_input(array(
						'id'	=> 'email',
						'name'	=> 'email',
						'class' => 'field',
						'value' => set_value('email')
						)
					)
				)
			);


			// Member Group Assignment
			if ($this->cp->allowed_group('can_admin_mbr_groups'))
			{
				$this->table->add_row(array(
						form_label(lang('member_group_assignment'), 'group_id').NBS.form_error('group_id'),
						form_dropdown('group_id', $member_groups, set_value('group_id', 5), 'id="group_id"')
					)
				);
			}

			echo $this->table->generate();
			?>
				<p><?=form_submit('members', lang('register_member'), 'class="submit"')?></p>

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

/* End of file register.php */
/* Location: ./themes/cp_themes/default/members/register.php */