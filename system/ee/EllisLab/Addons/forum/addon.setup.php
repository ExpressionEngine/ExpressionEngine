<?php

return array(
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Forum',
	'description'    => '',
	'version'        => '5.0.1',
	'namespace'      => 'EllisLab\Addons\Forum',
	'settings_exist' => TRUE,

	'files.directories' => array(

		'Signatures' => function()
		{
			return array(
				'path' => ee()->config->item('sig_img_path'),
				'url' => ee()->config->item('sig_img_url')
			);
		}
	),

	'models' => array(
		'Administrator'   => 'Model\Administrator',
		'Attachment'      => 'Model\Attachment',
		'Board'           => 'Model\Board',
		'Forum'           => 'Model\Forum',
		'Moderator'       => 'Model\Moderator',
		'Poll'            => 'Model\Poll',
		'PollVote'        => 'Model\PollVote',
		'Post'            => 'Model\Post',
		'Rank'            => 'Model\Rank',
		'Search'          => 'Model\Search',
		// 'Subscription' => 'Model\Subscription',
		'Topic'           => 'Model\Topic',
	),

	'models.dependencies' => array(
		'Administrator'   => array(
			'ee:Member',
			'ee:MemberGroup'
		),
		'Attachment'   => array(
			'ee:Member'
		),
		'Board'   => array(
			'ee:Site'
		),
		'Forum'   => array(
			'ee:Member'
		),
		'Moderator'   => array(
			'ee:Member',
			'ee:MemberGroup'
		),
		'Poll'   => array(
			'ee:Member'
		),
		'PollVote'   => array(
			'ee:Member'
		),
		'Post'   => array(
			'ee:Member'
		),
		'Search'   => array(
			'ee:Member'
		),
		'Topic'   => array(
			'ee:Member'
		),
	),
);

// EOF
