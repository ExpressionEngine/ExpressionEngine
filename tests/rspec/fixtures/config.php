<?php

function is_really_writable($file)
{
	return is_writable($file);
}

require('bootstrap.php');
require(APPPATH.'config/constants.php');
require(BASEPATH.'helpers/string_helper.php');
require(BASEPATH.'core/Config.php');

ee()->config = new EE_Config();

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
	$value = ee()->config->item($item);
	if (empty($value))
	{
		exit('empty');
	}
	exit($value);
}

$value = array_shift($argv);

ee()->config->update_site_prefs(array($item => $value), $site_id);
ee()->config->site_prefs('', $site_id);
print $item . ' is now ' . ee()->config->item($item);
