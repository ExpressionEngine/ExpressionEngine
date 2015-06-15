<?php extend_view('_wrapper') ?>

<?php $edit = (isset($rank) && is_array($rank)); ?>

<?=form_open($_form_base.AMP.'method=forum_update_rank'.($edit ? AMP.'rank_id='.$rank['rank_id'] : '')); ?>

<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<th colspan="2"><?=lang('forum_add_rank'); ?></th>
	</tr>
	<tr>
		<td style="width: 50%"><?=lang('forum_rank_title', 'rank_title'); ?></td>
		<td><?=form_input(array(
			'name'		=> 'rank_title',
			'id'		=> 'rank_title',
			'maxlength'	=> '100',
			'class'		=> 'field',
			'style'		=> 'width: 98%',
			'value'		=> $edit ? form_prep($rank['rank_title']) : ''
		)); ?></td>
	</tr>
	<tr>
		<td><?=lang('forum_rank_min_posts', 'rank_min_posts'); ?></td>
		<td><?=form_input(array(
			'name'		=> 'rank_min_posts',
			'id'		=> 'rank_min_posts',
			'maxlength'	=> '6',
			'class'		=> 'field',
			'style'		=> 'width: 20%',
			'value'		=> $edit ? $rank['rank_min_posts'] : ''
		)); ?></td>
	</tr>
	<tr>
		<td><?=lang('forum_rank_stars', 'rank_stars'); ?></td>
		<td><?=form_input(array(
			'name'		=> 'rank_stars',
			'id'		=> 'rank_stars',
			'maxlength'	=> '3',
			'class'		=> 'field',
			'style'		=> 'width: 20%',
			'value'		=> $edit ? $rank['rank_stars'] : ''
		)); ?></td>
	</tr>
</table>

<p><?=form_submit('submit', lang(($edit ? 'update' : 'submit')), 'class="submit"')?></p>
<?=form_close(); ?>