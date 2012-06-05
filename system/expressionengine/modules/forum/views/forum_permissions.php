<?php extend_view('_wrapper') ?>

<?=form_open($_form_base.AMP.'method=forum_update_permissions', '', $hidden)?>

<?php if ($forum_id == 'global'): ?>
	<p class="go_notice"><?=lang('forum_global_permissions_inst'); ?></p>
<?php else: ?>
	<p class="go_notice"><?=lang(($is_category === TRUE) ? 'permissions_for_cat' : 'permissions_for_forum').NBS.NBS.$forum_name; ?></p>
<?php endif; ?>


<?php
if ($is_category === TRUE)
{
	$this->table->set_heading(
		array('data' => lang('forum_member_group'),			'style' => 'width: 45%'),
		array('data' => lang('forum_cat_can_view'),			'style'	=> 'width: 25%'),
		array('data' => lang('forum_cat_can_view_hidden'),	'style'	=> 'width: 25%')
//		array('data' => NBS,								'style' => 'width: 5%')
	);
}
else
{
	$this->table->set_heading(
		array('data' => lang('forum_member_group'),			'style' => 'width: 23%'),
		array('data' => lang('forum_can_view'),				'style'	=> 'width: 9%'),
		array('data' => lang('forum_can_view_hidden'),		'style'	=> 'width: 9%'),
		array('data' => lang('forum_can_view_topics'),		'style'	=> 'width: 9%'),
		array('data' => lang('forum_can_post_topic'),		'style'	=> 'width: 9%'),
		array('data' => lang('forum_can_post_reply'),		'style'	=> 'width: 9%'),
		array('data' => lang('forum_can_upload'),			'style'	=> 'width: 9%'),
		array('data' => lang('forum_can_report'),			'style'	=> 'width: 9%'),
		array('data' => lang('forum_can_search'),			'style'	=> 'width: 9%')
//		array('data' => NBS,								'style' => 'width: 5%')
	);
}

foreach($groups as $permission_row)
{
	$row = array($permission_row['group_name']);
	
	foreach($permission_row['fields'] as $field => $val)
	{
		if ( ! is_bool($val))
		{
			$row[] = '-';
		}
		else
		{
			$row[] = form_checkbox($field.'['.$permission_row['group_id'].']', $permission_row['group_id'], $val);
		}
	}
	
	$this->table->add_row($row);
}

echo $this->table->generate();
?>

<?php if ($forum_id == 'global'): ?>
	
	<h4><?=lang('forum_use_deft_permissions')?></h4>
	<p>
		<?=lang('yes', 'use_deft_perm_y').NBS.form_radio('board_use_deft_permissions', 'y', ($use_default == 'y'), 'id="use_deft_perm_y"')?>
		&nbsp;&nbsp;&nbsp;&nbsp;
		<?=lang('no', 'use_deft_perm_n').NBS.form_radio('board_use_deft_permissions', 'n', ($use_default != 'y'), 'id="use_deft_perm_n"')?>
	</p>

<?php endif; ?>

<p><?=form_submit('submit', lang('update'), 'class="submit"')?></p>
