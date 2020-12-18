<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"count:",
	"site-id:",
	"member-id:",
	"screen-name:",
	"ip-address:",
	"timestamp-min:",
	"timestamp-max:",
	"type:",
	"terms:",
	"help",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help                   This help message
	--count         <number> The number of developer logs to generate
	--site-id       <number> The site_id to use
	--member-id     <number> The member_id to use
	--screen-name   <string> The screen_name to use
	--ip-address    <string> The ip_address to use
	--timestamp-min <number> The minimum number of hours to subtract from "now"
	--timestamp-max <number> The maximum number of hours to subtract from "now"
	--type          <string> The search_type to use
	--terms         <string> The search_terms to use
EOF;
	exit();
}

$count = isset($options['count']) && is_numeric($options['count']) ? (int) $options['count'] : 20;
$site_id = isset($options['site-id']) && is_numeric($options['site-id']) ? (int) $options['site-id'] : 1;
$member_id = isset($options['member-id']) && is_numeric($options['member-id']) ? (int) $options['member-id'] : 1;
$screen_name = isset($options['screen-name']) ? $options['screen-name'] : 'admin';
$ip_address = isset($options['ip-address']) ? $options['ip-address'] : '127.0.0.1';
$timestamp_min = isset($options['timestamp-min']) && is_numeric($options['timestamp-min']) ? (int) $options['timestamp-min'] : 0;
$timestamp_max = isset($options['timestamp-max']) && is_numeric($options['timestamp-max']) ? (int) $options['timestamp-max'] : 24*60; // 2 months
$type = isset($options['type']) ? $options['type'] : FALSE;
$terms = isset($options['terms']) ? $options['terms'] : FALSE;

$types = array('forum', 'site', 'wiki');
$default_terms = array(
	"ExpressionEngine",
	"ExpressionEngine",
	"Maker",
	"Manifesto",
	"lorem ipsum",
	"emoticon",
	"troll",
	"George R.R. Martin",
	"what does the fox say",
	"whatthefoxsay"
);

for ($x = 0; $x < $count; $x++)
{
	$fixture = ee('Model')->make('SearchLog');
	$fixture->site_id = $site_id;
	$fixture->member_id = $member_id;
	$fixture->screen_name = $screen_name;
	$fixture->ip_address = $ip_address;
	$fixture->search_date = strtotime("-" . rand($timestamp_min*60, $timestamp_max*60) . " minutes");
	$fixture->search_type = ($type !== FALSE) ? $type : $types[rand(0, count($types)-1)];
	$fixture->search_terms = ($terms !== FALSE) ? $terms : $default_terms[rand(0, count($default_terms)-1)];
	$fixture->save();
}
