<?php

require('bootstrap.php');

@set_time_limit(300);

$command = array_shift($argv);

$longopts = array(
	"group-id:",
	"username:",
	"screen-name:",
	"email:",
	"number:",
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
	--number        <string> Number of members to create
EOF;
	exit();
}

$number = isset($options['number']) && is_numeric($options['number']) ? (int) $options['number'] : 1;
$group_id = isset($options['group-id']) && is_numeric($options['group-id']) ? (int) $options['group-id'] : 5;
$username = isset($options['username']) ? $options['username'] : 'johndoe';
$screen_name = isset($options['screen-name']) ? $options['screen-name'] : 'John Doe';
$email = isset($options['email']) ? $options['email'] : 'john@nomail.com';

//get the total of existing members
$existing = ee('Model')->get('Member')->count();
$start = $existing + 1;
$total = $start + $number;

for ($n = $start; $n <= $total; $n++) {
	$member = ee('Model')->make('Member');
	$member->role_id = $group_id;
	$member->username = $username . $n;
	$member->screen_name = $screen_name . $n;
	$member->password = sha1("password");
	$member->salt = sha1("password");
	$member->language = 'english';
	$member->timezone = 'America/New_York';
	$member->email = $n . $email;
	$member->save();
	unset($member);
	//echo $member->getId() . "\n";
}
