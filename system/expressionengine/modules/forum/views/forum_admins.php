<?php extend_view('_wrapper') ?>

<?php if (count($admins)): ?>

	<?php
	$this->table->set_heading(
		lang('forum_admins'),
		lang('forum_admin_type'),
		lang('remove')
	);

	foreach($admins as $type => $details)
	{
		foreach($details as $id => $name)
		{
			$this->table->add_row(
				$name,
				lang($type),
				'<a href="'.$_id_base.AMP.'method=forum_remove_admin_confirm'.AMP.'admin_id='.$id.'">'.lang('remove').'</a>'
			);
		}
	}

	echo $this->table->generate();
	$this->table->clear();
	?>

<?php else: ?>
	<p class="notice"><?=lang('forum_no_admins')?></p>
<?php endif; ?>


<?=form_open($_form_base.AMP.'method=forum_create_admin'); ?>

<p class="go_notice"><?=lang('forum_admin_inst')?></p>

<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th colspan="3"><?=lang('forum_new_admin'); ?></th>
	</tr>
	<tr>
		<td style="width: 33%"><?=lang('forum_admin_type', 'admin_type'); ?></td>
		<td style="width: 33%"><?=lang('username', 'admin_name'); ?></td>
		<td style="width: 33%"><?=lang('forum_member_group', 'admin_group_id'); ?></td>
	</tr>
	<tr>
		<td><?=form_dropdown('admin_type', array(
			'member'	=> lang('forum_type_member'),
			'group'		=> lang('forum_type_group')
		))?></td>

		<td><?=form_input(array(
			'name'		=> 'admin_name',
			'id'		=> 'admin_name',
			'maxlength'	=> '100',
			'class'		=> 'field',
			'style'		=> 'width: 50%'
		)); ?>
			<a id="forum_user_lookup" href="<?=$_id_base.AMP.'method=forum_user_lookup'.AMP.'from=admin'?>"><?=lang('forum_find_user')?></a><br />
			<p class="notice"><?=lang('forum_mod_name_inst')?></p>
		</td>

		<td><?=form_dropdown('admin_group_id', $member_groups); ?></td>
	</tr>
</table>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?=form_close(); ?>

<?php $this->load->view('username_modal'); ?>