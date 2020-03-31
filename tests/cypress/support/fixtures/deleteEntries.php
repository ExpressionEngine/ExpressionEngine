<?php

require('bootstrap.php');

$command = array_shift($argv);

$longopts = array(
	"help",
);

$options = getopt('h', $longopts);

if (isset($options['h']) || isset($options['help']))
{
  print <<<EOF
Delete entries from your EE site
Usage: {$command} [options]
	--help                   This help message
EOF;
	exit();
}


$entries = ee('Model')->get('ChannelEntry')->delete();

