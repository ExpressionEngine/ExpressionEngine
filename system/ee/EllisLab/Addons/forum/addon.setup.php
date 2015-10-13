<?php

return array(
	'author'         => 'EllisLab',
	'author_url'     => 'https://ellislab.com/',
	'name'           => 'Forum',
	'description'    => '',
	'version'        => '3.1.19',
	'namespace'      => 'EllisLab\Addons\Forum',
	'settings_exist' => TRUE,

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
	)
);
