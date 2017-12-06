<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"group-id:",
	"username:",
	"screen-name:",
	"email:",
	"help",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help                   This help message
	--group-id      <number> The group_id to use
	--username      <string> The username to use
	--screen-name   <string> The screen_name to use
	--email         <string> The email to use
EOF;
	exit();
}

$group_id = isset($options['group-id']) && is_numeric($options['group-id']) ? (int) $options['group-id'] : 5;
$username = isset($options['username']) ? $options['username'] : 'johndoe';
$screen_name = isset($options['screen-name']) ? $options['screen-name'] : 'John Doe';
$email = isset($options['email']) ? $options['email'] : 'john@nomail.com';

$member = ee('Model')->make('Member');
$member->group_id = $group_id;
$member->username = $username;
$member->screen_name = $screen_name;
$member->password = sha1("password");
$member->salt = sha1("password");
$member->language = 'english';
$member->timezone = 'America/New_York';
$member->email = $email;
$member->save();

