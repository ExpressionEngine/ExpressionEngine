<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"count:",
	"ip-address:",
	"timestamp-min:",
	"timestamp-max:",
	"hits:",
	"locked-out",
	"help",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} [options]
	--help					 This help message
	--count			<number> The number of developer logs to generate
	--ip-address	<string> The ip_address to use
	--timestamp-min <number> The minimum number of hours to subtract from "now"
	--timestamp-max <number> The maximum number of hours to subtract from "now"
	--hits			<number> The site_id to use
	--locked-out			 If used this will mark the entry as locked out
EOF;
	exit();
}

$count = isset($options['count']) && is_numeric($options['count']) ? (int) $options['count'] : 20;
$ip_address = isset($options['ip-address']) ? $options['ip-address'] : FALSE;
$timestamp_min = isset($options['timestamp-min']) && is_numeric($options['timestamp-min']) ? (int) $options['timestamp-min'] : 0;
$timestamp_max = isset($options['timestamp-max']) && is_numeric($options['timestamp-max']) ? (int) $options['timestamp-max'] : 24*60; // 2 months
$hits = isset($options['hits']) && is_numeric($options['hits']) ? (int) $options['hits'] : FALSE;
$locked_out = isset($options['locked-out']) ? TRUE : FALSE;

for ($x = 0; $x < $count; $x++)
{
	$fixture = ee('Model')->make('Throttle');
	$fixture->ip_address = ($ip_address !== FALSE) ? $ip_address : '10.0.' . rand(0,253) . '.' . rand(1,253);
	$fixture->last_activity = strtotime("-" . rand($timestamp_min*60, $timestamp_max*60) . " minutes");
	$fixture->hits = ($hits !== FALSE) ? $hits : rand(10, 100);
	$fixture->locked_out = $locked_out;
	$fixture->save();
}
