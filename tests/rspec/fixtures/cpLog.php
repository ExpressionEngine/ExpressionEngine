<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"count:",
	"site-id:",
	"member-id:",
	"username:",
	"ip-address:",
	"timestamp-min:",
	"timestamp-max:",
	"action:",
	"help",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help					 This help message
	--count			<number> The number of developer logs to generate
	--site-id		<number> The site_id to use
	--member-id		<number> The member_id to use
	--username		<string> The username to use
	--ip-address	<string> The ip_address to use
	--timestamp-min <number> The minimum number of hours to subtract from "now"
	--timestamp-max <number> The maximum number of hours to subtract from "now"
	--action		<string> The action to use
EOF;
	exit();
}

$count = isset($options['count']) && is_numeric($options['count']) ? (int) $options['count'] : 20;
$site_id = isset($options['site-id']) && is_numeric($options['site-id']) ? (int) $options['site-id'] : 1;
$member_id = isset($options['member-id']) && is_numeric($options['member-id']) ? (int) $options['member-id'] : 1;
$username = isset($options['username']) ? $options['username'] : 'admin';
$ip_address = isset($options['ip-address']) ? $options['ip-address'] : '127.0.0.1';
$timestamp_min = isset($options['timestamp-min']) && is_numeric($options['timestamp-min']) ? (int) $options['timestamp-min'] : 0;
$timestamp_max = isset($options['timestamp-max']) && is_numeric($options['timestamp-max']) ? (int) $options['timestamp-max'] : 24*60; // 2 months
$action = isset($options['action']) ? $options['action'] : FALSE;

$actions = array(
	'Category Group Created:&nbsp;&nbsp;Foo',
	'Channel Created:&nbsp;&nbsp;Bar',
	'Field Group Crated:&nbsp;&nbsp;Baz',
	'Logged in',
	'Logged out',
	'Member Group Crated:&nbsp;&nbsp;Group One',
	'Member Group Updated:&nbsp;&nbsp;Group Two',
	'Member profile created:&nbsp;&nbsp;jdoe'
);

for ($x = 0; $x < $count; $x++)
{
	$fixture = ee('Model')->make('CpLog');
	$fixture->site_id = $site_id;
	$fixture->member_id = $member_id;
	$fixture->username = $username;
	$fixture->ip_address = $ip_address;
	$fixture->act_date = strtotime("-" . rand($timestamp_min*60, $timestamp_max*60) . " minutes");
	$fixture->action = ($action !== FALSE) ? $action : $actions[rand(0, count($actions)-1)];
	$fixture->save();
}
