<?php

return array(
	'author'      => 'EllisLab',
	'author_url'  => 'https://ellislab.com/',
	'name'        => 'Forum',
	'description' => '',
	'version'     => '3.1.19',
	'namespace'   => 'EllisLab\Addons\Forum',
	'settings_exist' => TRUE,

	'models' => array(
		'Attachmenet' => 'Model\Attachmenet',
		'Board' => 'Model\Board',
		'Forum' => 'Model\Forum',
		'Moderator' => 'Model\Moderator',
		'Poll' => 'Model\Poll',
		'PollVote' => 'Model\PollVote',
		'Post' => 'Model\Post',
		'Rank' => 'Model\Rank',
		'Search' => 'Model\Search',
		// 'Subscription' => 'Model\Subscription',
		'Topic' => 'Model\Topic',
	)
);