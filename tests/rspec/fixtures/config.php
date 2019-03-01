<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"site-id:",
	"raw",
	"help",
);

$options = getopt('h', $longopts, $optind);

if (empty($argv) || isset($options['h']) || isset($options['help']))
{
	print <<<EOF
Usage: {$command} <config_item> [value] [options]
		--site-id <number> The site_id to use (default: 1)
EOF;
	exit();
}

$site_id = isset($options['site-id']) && is_numeric($options['site-id']) ? (int) $options['site-id'] : 1;
$index = '';
$raw = isset($options['raw']) ? TRUE : FALSE;

ee()->config->site_prefs('', $site_id);

$argv = array_slice($argv, $optind - 1);

$item = array_shift($argv);

if (empty($argv))
{
	$value = ee()->config->item($item, $index, $raw);
	if (empty($value))
	{
		exit('empty');
	}
	exit((string)$value);
}

$value = array_shift($argv);

ee()->config->update_site_prefs(array($item => $value), $site_id);
ee()->config->site_prefs('', $site_id);
print $item . ' is now ' . ee()->config->item($item, $index, $raw);

// EOF
