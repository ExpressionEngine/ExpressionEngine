<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"title:",
	"description:",
	"site-id:",
	"channel-id:",
	"help"
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help         This help message
	--title        <string> The title to use
	--description  <string> The description to use
	--site-id      <number> The site_id to use
	--channel-id   <number> A channel to assign to this group
EOF;
	exit();
}

$site_id = isset($options['site-id']) && is_numeric($options['site-id']) ? (int) $options['site-id'] : 1;
$title = isset($options['title']) ? $options['title'] : 'Some Group';
$description = isset($options['description']) ? $options['description'] : '';
$channel_id = isset($options['channel-id']) && is_numeric($options['channel-id']) ? (int) $options['channel-id'] : NULL;

$new_group_id = ee('Model')->get('MemberGroup')
	->order('group_id', 'desc')
	->limit(1)
	->first()
	->group_id + 1;

$group = ee('Model')->make('MemberGroup');
$group->group_id = $new_group_id;
$group->site_id = $site_id;
$group->group_title = $title;
$group->group_description = $description;
$group->save();

if ($channel_id)
{
	$channels = ee('Model')->get('Channel', $channel_id)->all();
	$group->setAssignedChannels($channels);
}
