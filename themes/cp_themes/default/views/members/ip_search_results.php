<?php extend_template('default') ?>

<?php if ($grand_total == 0):?>
	<p class="notice"><?=lang('no_members_matching_that_criteria')?></p>
<?php else:?>

	<?php
		//  Find Member Accounts with IP
		if ($members_accounts->num_rows() > 0):

			$this->table->set_heading(
				lang('username'),
				lang('screen_name'),
				lang('mbr_email_address'),
				lang('ip_address')
			);

			foreach ($members_accounts->result() as $member)
			{
				$this->table->add_row(
					array('style'=>'width:40%;', 'data'=>'<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='.$member->member_id.'">'.$member->username.'</a>'),
					array('style'=>'width:20%;', 'data'=>$member->screen_name),
					array('style'=>'width:20%;', 'data'=>'<a href="mailto:'.$member->email.'">'.$member->email.'</a>'),
					array('style'=>'width:20%;', 'data'=>$member->ip_address)
				);
			}

			$member_accounts_table = $this->table->generate();
			$this->table->clear(); // get out of the way for the next table

	?>
	<div id="filterMenu">
		<h3><?=lang('member_accounts')?></h3>
		<?=$member_accounts_table?><br />
		<?=$member_accounts_pagination?>
	</div>
	<?php endif; /*end member accounts*/?>

	<?php
		//  Find Channel Entries with IP
		if ($channel_entries->num_rows() > 0):

			if ($this->config->item('multiple_sites_enabled') !== 'y')
			{
				$this->table->set_heading(
					array('style'=>'width:40%;', 'data'=>lang('title')),
					array('style'=>'width:20%;', 'data'=>lang('screen_name')),
					array('style'=>'width:20%;', 'data'=>lang('mbr_email_address')),
					array('style'=>'width:20%;', 'data'=>lang('ip_address'))
				);
			}
			else
			{
				$this->table->set_heading(
					array('style'=>'width:40%;', 'data'=>lang('title')),
					array('style'=>'width:15%;', 'data'=>lang('site')),
					array('style'=>'width:15%;', 'data'=>lang('screen_name')),
					array('style'=>'width:15%;', 'data'=>lang('mbr_email_address')),
					array('style'=>'width:15%;', 'data'=>lang('ip_address'))
				);
			}

			foreach ($channel_entries->result() as $channel)
			{
				if ($this->config->item('multiple_sites_enabled') !== 'y')
				{
					$this->table->add_row(
						'<a href="'.BASE.AMP.'C=edit'.AMP.'M=view_entry'.AMP.'channel_id='.$channel->channel_id.AMP.'entry_id='.$channel->entry_id.'">'.$channel->title.'</a>',
						$channel->screen_name,
						'<a href="mailto:'.$channel->email.'">'.$channel->email.'</a>',
						$channel->ip_address
					);
				}
				else
				{
					$this->table->add_row(
						'<a href="'.BASE.AMP.'C=edit'.AMP.'M=view_entry'.AMP.'channel_id='.$channel->channel_id.AMP.'entry_id='.$channel->entry_id.'">'.$channel->title.'</a>',
						$channel->site_label,
						$channel->screen_name,
						'<a href="mailto:'.$channel->email.'">'.$channel->email.'</a>',
						$channel->ip_address
					);
				}
			}

			$channel_entries_table = $this->table->generate();
			$this->table->clear(); // get out of the way for the next table

	?>
	<div id="filterMenu">
		<h3><?=lang('channel_entries')?></h3>
		<?=$channel_entries_table?><br />
		<?=$channel_entries_pagination?>
	</div>
	<?php endif; /* end channel entries*/ ?>

	<?php
	//  Find Comments with IP
		if (isset($comments) && $comments->num_rows() > 0):

			$this->table->set_heading(
				array('style'=>'width:40%;', 'data'=>lang('comment')),
				array('style'=>'width:20%;', 'data'=>lang('author')),
				array('style'=>'width:20%;', 'data'=>lang('mbr_email_address')),
				array('style'=>'width:20%;', 'data'=>lang('ip_address'))
			);

			foreach ($comments->result() as $comment)
			{
				if ($comment->author_id != 0)
				{
					$author = '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='.$comment->author_id.'"><b>'.$comment->name.'</b></a>';
				}
				else
				{
					$author = '<b>'.$comment->name.'</b>';
				}

				$this->table->add_row(
					'<a href="'.BASE.AMP.'C=publish'.AMP.'M=edit_comment'.AMP.'channel_id='.$comment->channel_id.AMP.'entry_id='.$comment->entry_id.AMP.'comment_id='.$comment->comment_id.AMP.'current_page=0"><b>'.substr(strip_tags($comment->comment), 0, 45).'...</b></a>',
					$author,
					'<a href="mailto:'.$comment->email.'">'.$comment->email.'</a>',
					$comment->ip_address
				);
			}

			$comments_table = $this->table->generate();
			$this->table->clear(); // get out of the way for the next table
	?>
	<div id="filterMenu">
		<h3><?=lang('comments')?></h3>
		<?=$comments_table?><br />
		<?=$comments_pagination?>
	</div>

	<?php endif; /*end comments*/?>


	<?php
		// Find Forum Topics with IP

		if (isset($forum_topics) && $forum_topics->num_rows() > 0):

			$this->table->set_heading(
				array('style'=>'width:40%;', 'data'=>lang('topic')),
				array('style'=>'width:20%;', 'data'=>lang('author')),
				array('style'=>'width:20%;', 'data'=>lang('email')),
				array('style'=>'width:20%;', 'data'=>lang('ip_address'))
			);

			foreach ($forum_topics->result() as $forum_topic)
			{
				$forum_topic->title = str_replace(array('<', '>', '{', '}', '\'', '"', '?'), array('&lt;', '&gt;', '&#123;', '&#125;', '&#146;', '&quot;', '&#63;'), $forum_topic->title);

				$this->table->add_row(
					'<a href="'.reduce_double_slashes($forum_topic->board_forum_url.'/viewthread/').$forum_topic->topic_id.'/'.'"><b>'.$forum_topic->title.'</b></a>',
					'<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='.$forum_topic->member_id.'"><b>'.$forum_topic->screen_name.'</b></a>',
					'<a href="mailto:'.$forum_topic->email.'">'.$forum_topic->email.'</a>',
					$forum_topic->ip_address
				);
			}

			$forum_topics_table = $this->table->generate();
			$this->table->clear(); // get out of the way for the next table
	?>

	<div id="filterMenu">
		<h3><?=lang('forum_topics')?></h3>
		<?=$forum_topics_table?><br />
		<?=$forum_topics_pagination?>
	</div>
	<?php endif; /* forum_topics */?>

	<?php
		// Find Forum Posts with IP

		if (isset($forum_posts) && $forum_posts->num_rows() > 0):

			$this->table->set_heading(
				array('style'=>'width:40%;', 'data'=>lang('topic')),
				array('style'=>'width:20%;', 'data'=>lang('author')),
				array('style'=>'width:20%;', 'data'=>lang('email')),
				array('style'=>'width:20%;', 'data'=>lang('ip_address'))
			);

			foreach ($forum_posts->result() as $forum_post)
			{

				$this->table->add_row(
					'<a href="'.reduce_double_slashes($forum_post->board_forum_url.'/viewreply/').$forum_post->post_id.'/'.'"><b>'.substr(strip_tags($forum_post->body), 0, 45).'....</b></a>',
					'<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='.$forum_post->member_id.'"><b>'.$forum_post->screen_name.'</b></a>',
					'<a href="mailto:'.$forum_post->email.'">'.$forum_post->email.'</a>',
					$forum_post->ip_address
				);
			}

			$forum_posts_table = $this->table->generate();
			$this->table->clear(); // get out of the way for the next table
	?>
	<div id="filterMenu">
		<h3><?=lang('forum_posts')?></h3>
		<?=$forum_posts_table?><br />
		<?=$forum_posts_pagination?>
	</div>
	<?php endif; /*end forum_posts */?>
<?php endif;?>