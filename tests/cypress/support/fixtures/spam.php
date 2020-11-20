<?php

$boot_with_spam = TRUE;
require 'bootstrap.php';
require_once 'vendor/fzaninotto/faker/src/autoload.php';

$command = array_shift($argv);

$longopts = [
	"total:",
	"entry-id:",
];

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help       This help message
	--total      <number> The total comments to toss into the spam queue (default: 5)
EOF;
	exit();
}

$total = isset($options['total']) ? $options['total'] : 5;


$entry =ee('Model')->get('ChannelEntry');
if (isset($options['entry-id']))
{
	$entry->filter('entry_id', $options['entry-id']);
}

$entry = $entry->first();

if ( ! $entry)
{
	exit('Spam Fixture requires the existence of at least one entry');
}

$member =ee('Model')->get('Member')->first();

$faker = Faker\Factory::create();

for ($i = 0; $i < $total; $i++)
{
	$time = time() - mt_rand(0, 7200);
	$ip = $faker->ipv4;
	$comment_text = $faker->words(mt_rand(10, 50), TRUE);

	$comment_data = [
		'channel_id'   => $entry->channel_id,
		'entry_id'     => $entry->entry_id,
		'comment'      => $comment_text,
		'comment_date' => $time,
		'ip_address'   => $ip,
		'status'       => 's',
		'site_id'      => $entry->site_id,
	];

	switch (mt_rand(0,1))
	{
		case 0:
			$author_id = 0;
			$comment_data['author_id'] = $author_id;
			$comment_data['name']      = $faker->name;
			$comment_data['email']     = $faker->safeEmail;
			$comment_data['url']       = 'http://'.$faker->domainName;
			$comment_data['location']  = $faker->city.', '.$faker->stateAbbr;
			break;
		case 1:
			$author_id = $member->member_id;
			$comment_data['author_id'] = $member->member_id;
			$comment_data['name']      = $member->screen_name;
			$comment_data['email']     = $member->email;
			$comment_data['url']       = ($member->url) ?: '';
			$comment_data['location']  = ($member->location) ?: '';
			break;
	}

	$comment =ee('Model')->make('Comment', $comment_data)->save();

	$spam_data = [
		'content_type'  => 'comment',
		'author_id'     => $author_id,
		'trap_date'     => $time,
		'ip_address'    => $ip,
		'entity'        => $comment,
		'document'      => $comment_text,
		'optional_data' => '/fake/uri',
	];

	$trap =ee('Model')->make('spam:SpamTrap', $spam_data);
	$trap->save();
}
