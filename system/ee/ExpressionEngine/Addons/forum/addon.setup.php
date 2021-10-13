<?php

return array(
    'author' => 'ExpressionEngine',
    'author_url' => 'https://expressionengine.com/',
    'name' => 'Forum',
    'description' => 'Add a full-featured forum to your site',
    'version' => '5.0.2',
    'namespace' => 'ExpressionEngine\Addons\Forum',
    'settings_exist' => true,

    'files.directories' => array(

        'Signatures' => function () {
            return array(
                'path' => ee()->config->item('sig_img_path'),
                'url' => ee()->config->item('sig_img_url')
            );
        }
    ),

    'models' => array(
        'Administrator' => 'Model\Administrator',
        'Attachment' => 'Model\Attachment',
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
    ),

    'models.dependencies' => array(
        'Administrator' => array(
            'ee:Member',
            'ee:Role'
        ),
        'Attachment' => array(
            'ee:Member'
        ),
        'Board' => array(
            'ee:Site'
        ),
        'Forum' => array(
            'ee:Member'
        ),
        'Moderator' => array(
            'ee:Member',
            'ee:Role'
        ),
        'Poll' => array(
            'ee:Member'
        ),
        'PollVote' => array(
            'ee:Member'
        ),
        'Post' => array(
            'ee:Member'
        ),
        'Search' => array(
            'ee:Member'
        ),
        'Topic' => array(
            'ee:Member'
        ),
    ),
    'cookies.functionality' => [
        'forum_theme',
        'forum_topics'
    ],
    'cookie_settings' => [
        'forum_theme' => [
            'description' => 'lang:cookie_forum_theme_desc'
        ],
        'forum_topics' => [
            'description' => 'lang:cookie_forum_topics_desc'
        ]
    ],
);

// EOF
