<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"site-id:",
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

ee()->config->site_prefs('', $site_id);

$item = array_shift($argv);

if (empty($argv))
{
	$value = ee()->config->item($item, '', true);
	if (empty($value))
	{
		exit('');
	}
	$value = preg_replace("/\n/", "\\n", $value);
	exit((string)$value);
}

$value = array_shift($argv);

ee()->config->update_site_prefs(array($item => $value), $site_id);
ee()->config->site_prefs('', $site_id);
print $item . ' is now ' . ee()->config->item($item);

// EOF
