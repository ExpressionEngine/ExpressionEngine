<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"site-id:",
	"raw",
	"help",
);

$options = getopt('h', $longopts);

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

$item = NULL;
$value = NULL;

$item = array_pop($argv);

if ( ! empty($argv))
{
	$arg = array_pop($argv);

	if ($arg[0] != '-' && (end($argv) != '--site-id'))
	{
		$value = $item;
		$item = $arg;
	}
}

if (empty($value))
{
	$value = ee()->config->item($item, $index, $raw);
	if (empty($value))
	{
		exit('empty');
	}
	exit((string)$value);
}

ee()->config->update_site_prefs(array($item => $value), $site_id);
ee()->config->site_prefs('', $site_id);
print $item . ' is now ' . ee()->config->item($item, $index, $raw);

// EOF
