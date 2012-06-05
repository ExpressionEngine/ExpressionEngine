<span class="cp_button"><a href="<?=$_id_base.AMP.'method=forum_prefs'?>"><?=lang('forum_prefs')?></a></span>
<span class="cp_button"><a href="<?=$_id_base.AMP.'method=forum_permissions'.AMP.'forum_id=global'?>"><?=lang('forum_global_permissions')?></a></span>

<div class="clear_left"></div>

<?php
if (count($forums)):
	$this->table->set_heading(
		lang('forum_id'),
		lang('forum_name'),
		lang('forum_total_topics'),
		lang('forum_total_topics_perday'),
		lang('forum_total_posts'),
		lang('forum_total_post_perday')
	);

	foreach($forums as $forum)
	{
		$this->table->add_row(
			$forum['forum_id'],
			$forum['forum_name'],
			$forum['forum_total_topics'],
			$forum['topics_perday'],
			$forum['forum_total_posts'],
			$forum['posts_perday']
		);
	}
	
	echo $this->table->generate();
else:
?>
<p class="notice"><?=lang('no_forums_for_forum_board')?></p>
<?php endif; ?>