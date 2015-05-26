<?php extend_view('_wrapper') ?>

<?=form_open($_form_base.AMP.'method=forum_update_moderator', '', $hidden); ?>

<p class="go_notice"><?=lang('forum_moderator_inst')?></p>

<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th colspan="3"><?=lang('forum_current').NBS.NBS.$current_forum['forum_name']; ?></th>
	</tr>
	<tr>
		<td style="width: 33%"><?=lang('forum_mod_type', 'mod_type'); ?></td>
		<td style="width: 33%"><?=lang('username', 'mod_name'); ?></td>
		<td style="width: 33%"><?=lang('forum_member_group', 'mod_group_id'); ?></td>
	</tr>
	<tr>
		<td><?=form_dropdown(
			'mod_type',
			array(
				'member'	=> lang('forum_type_member'),
				'group'		=> lang('forum_type_group')
			),
			($mod_group_id == 0) ? 'member' : 'group'
		)?></td>
		
		<td><?=form_input(array(
			'name'		=> 'mod_name',
			'id'		=> 'mod_name',
			'maxlength'	=> '100',
			'class'		=> 'field',
			'style'		=> 'width: 50%',
			'value'		=> $mod_name
		)); ?>
			<a id="forum_user_lookup" href="<?=$_id_base.AMP.'method=forum_user_lookup'.AMP.'from=mod'?>"><?=lang('forum_find_user')?></a><br />
			<p class="notice"><?=lang('forum_mod_name_inst')?></p>
		</td>
		
		<td><?=form_dropdown('mod_group_id', $member_groups, $mod_group_id); ?></td>
	</tr>
</table>

<?php

$this->table->set_heading(
	array('data' => lang('forum_permission'),	'style' => 'width="50%"'),
	array('data' => lang('forum_value'),		'style' => 'width="50%"')
);

foreach($matrix as $key => $val)
{
	$y = array(
	    'name'        => $key,
	    'id'          => $key.'_y',
	    'value'       => 'y',
	    'checked'     => ($val == 'y')
	    );

	$n = array(
	    'name'        => $key,
	    'id'          => $key.'_n',
	    'value'       => 'n',
	    'checked'     => ($val == 'n')
	    );

	$this->table->add_row(
		lang($key),
		
		lang('yes', $key.'_y').NBS.form_radio($y).NBS.NBS.NBS.
		lang('no', $key.'_n').NBS.form_radio($n)
	);
}

echo $this->table->generate();

?>
<p><?=form_submit('submit', $is_new ? lang('submit') : lang('update'), 'class="submit"')?></p>

<?=form_close(); ?>

<?php $this->load->view('username_modal'); ?>